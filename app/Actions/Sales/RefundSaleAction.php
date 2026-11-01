<?php

namespace App\Actions\Sales;

use App\Models\AuditLog;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;

class RefundSaleAction
{
    /**
     * Refund a completed sale and restore inventory for the returned items.
     *
     * Pass ``items`` as an array of ``['product_id' => ..., 'variant_id' => ..., 'quantity' => ...]``
     * to refund only those quantities (partial return). When omitted, the whole
     * sale is refunded.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): SaleReturn
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::query()->with(['items', 'returns.items'])->whereKey($data['sale_id'])->firstOrFail();
            $tenantId = $data['tenant_id'] ?? $sale->tenant_id;
            $branchId = $data['branch_id'] ?? $sale->branch_id;
            $reason = $data['reason'] ?? 'Sale refund';
            $returnedBy = $data['returned_by'] ?? $sale->sold_by;

            $requested = $this->requestedQuantities($data['items'] ?? null);
            $alreadyReturned = $this->alreadyReturnedByKey($sale);

            $refundAmount = 0.0;
            $returnItems = [];

            foreach ($sale->items as $item) {
                $quantityToRefund = $this->quantityToRefund($item, $requested, $alreadyReturned);
                if ($quantityToRefund <= 0) {
                    continue;
                }

                $unitNet = $this->unitNet($item);
                $returnItems[] = [
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id ?? null,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $quantityToRefund,
                    'unit_price' => $unitNet,
                    'discount_amount' => $quantityToRefund > 0 ? $this->proportional($item->discount_amount ?? 0, $item->quantity, $quantityToRefund) : 0,
                    'tax_amount' => $this->proportional($item->tax_amount ?? 0, $item->quantity, $quantityToRefund),
                    'line_total' => round($unitNet * $quantityToRefund, 4),
                    'cost_price' => $item->cost_price ?? null,
                ];

                $refundAmount += $unitNet * $quantityToRefund;
            }

            if ($returnItems === []) {
                throw new \RuntimeException('No returnable items were selected.');
            }

            $saleReturn = SaleReturn::query()->create([
                'sale_id' => $sale->id,
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'reference_no' => $data['reference_no'] ?? 'RET-'.now()->format('Y-m-d').'-'.str_pad((string) (SaleReturn::where('tenant_id', $tenantId)->count() + 1), 5, '0', STR_PAD_LEFT),
                'reason' => $reason,
                'refund_amount' => number_format($refundAmount, 4, '.', ''),
                'refund_method_id' => $data['refund_method_id'] ?? null,
                'status' => 'completed',
                'returned_by' => $returnedBy,
                'returned_at' => now(),
            ]);

            foreach ($returnItems as $returnItem) {
                SaleReturnItem::query()->create([
                    'sale_return_id' => $saleReturn->id,
                    'product_id' => $returnItem['product_id'],
                    'variant_id' => $returnItem['variant_id'],
                    'quantity' => $returnItem['quantity'],
                    'unit_price' => $returnItem['unit_price'],
                    'discount_amount' => $returnItem['discount_amount'],
                    'tax_amount' => $returnItem['tax_amount'],
                    'line_total' => $returnItem['line_total'],
                ]);

                $this->restoreStock(
                    tenantId: $tenantId,
                    branchId: $branchId,
                    productId: $returnItem['product_id'],
                    variantId: $returnItem['variant_id'],
                    quantity: $returnItem['quantity'],
                    unitCost: $returnItem['cost_price'],
                    reason: $reason,
                    returnedBy: $returnedBy,
                    saleReturnId: $saleReturn->id,
                );
            }

            $sale->status = $this->isFullyRefunded($sale, $returnItems, $alreadyReturned) ? 'refunded' : 'partially_refunded';
            $sale->save();

            AuditLog::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'user_id' => $returnedBy,
                'event' => 'sale.returned',
                'auditable_type' => SaleReturn::class,
                'auditable_id' => $saleReturn->id,
                'old_values' => ['status' => $sale->getOriginal('status')],
                'new_values' => ['status' => $sale->status, 'refund_amount' => $refundAmount],
            ]);

            return $saleReturn;
        });
    }

    /**
     * Normalise the requested return quantities into a per-line key map.
     *
     * @return array<string, float>
     */
    private function requestedQuantities(?array $items): array
    {
        $map = [];
        if ($items === null) {
            return $map;
        }

        foreach ($items as $item) {
            $key = ($item['product_id'] ?? '').'|'.($item['variant_id'] ?? '');
            $map[$key] = (float) ($item['quantity'] ?? 0);
        }

        return $map;
    }

    /**
     * How much of a sale line to refund on this return.
     *
     * @param  array<string, float>  $requested
     * @param  array<string, float>  $alreadyReturned
     */
    private function quantityToRefund(SaleItem $item, array $requested, array $alreadyReturned): float
    {
        if ($requested === []) {
            return (float) $item->quantity;
        }

        $key = ($item->product_id).'|'.($item->variant_id ?? '');
        $requestedQty = $requested[$key] ?? 0;

        if ($requestedQty <= 0) {
            return 0;
        }

        // Cap at the remaining returnable quantity (sold minus already returned).
        $remaining = (float) $item->quantity - ($alreadyReturned[$key] ?? 0);

        return max(0, min($requestedQty, $remaining));
    }

    /**
     * Total quantity of each sale line already returned on earlier returns.
     *
     * Keyed by `product_id|variant_id`, so a sale with many items and many
     * prior returns only needs one pass over the already-eager-loaded relations
     * instead of one per line.
     *
     * @return array<string, float>
     */
    private function alreadyReturnedByKey(Sale $sale): array
    {
        $map = [];

        foreach ($sale->returns ?? [] as $existingReturn) {
            foreach ($existingReturn->items as $existingItem) {
                $key = ($existingItem->product_id).'|'.($existingItem->variant_id ?? null);
                $map[$key] = ($map[$key] ?? 0) + (float) $existingItem->quantity;
            }
        }

        return $map;
    }

    /**
     * Net unit price for a sale line (line total per unit).
     */
    private function unitNet(SaleItem $item): float
    {
        $qty = (float) $item->quantity;

        return $qty > 0 ? (float) $item->total_amount / $qty : 0;
    }

    /**
     * Pro-rate a line-level amount across a partial quantity.
     */
    private function proportional(string|float|int $amount, string|float|int $totalQty, float $returnedQty): float
    {
        $total = (float) $totalQty;
        if ($total <= 0) {
            return 0;
        }

        return round(((float) $amount / $total) * $returnedQty, 4);
    }

    /**
     * Whether every sale line is fully returned after this return.
     *
     * @param  array<int, array<string, mixed>>  $returnItems
     * @param  array<string, float>  $alreadyReturned
     */
    private function isFullyRefunded(Sale $sale, array $returnItems, array $alreadyReturned): bool
    {
        $newlyReturned = [];
        foreach ($returnItems as $item) {
            $key = ($item['product_id'] ?? '').'|'.($item['variant_id'] ?? '');
            $newlyReturned[$key] = ($newlyReturned[$key] ?? 0) + (float) $item['quantity'];
        }

        foreach ($sale->items as $item) {
            $key = ($item->product_id).'|'.($item->variant_id ?? '');

            $totalReturned = ($alreadyReturned[$key] ?? 0) + ($newlyReturned[$key] ?? 0);

            if ($totalReturned < (float) $item->quantity - 0.0001) {
                return false;
            }
        }

        return true;
    }

    /**
     * Restore stock for a returned line and log the inventory transaction.
     */
    private function restoreStock(
        string $tenantId,
        string $branchId,
        string $productId,
        ?string $variantId,
        string|float|int $quantity,
        string|float|int|null $unitCost,
        string $reason,
        int $returnedBy,
        string $saleReturnId,
    ): void {
        $inventory = Inventory::query()
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            $inventory = Inventory::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => 0,
            ]);
        }

        $inventory->increment('quantity', $quantity);

        InventoryTransaction::query()->create([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'type' => 'sale_return',
            'reference_type' => SaleReturn::class,
            'reference_id' => $saleReturnId,
            'note' => $reason,
            'created_by' => $returnedBy,
        ]);
    }
}
