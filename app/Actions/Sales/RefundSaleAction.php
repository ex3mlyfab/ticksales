<?php

namespace App\Actions\Sales;

use App\Models\AuditLog;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;

class RefundSaleAction
{
    /**
     * Refund a completed sale and restore inventory for the returned items.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): SaleReturn
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::query()->with('items')->findOrFail($data['sale_id']);
            $tenantId = $data['tenant_id'] ?? $sale->tenant_id;
            $branchId = $data['branch_id'] ?? $sale->branch_id;
            $reason = $data['reason'] ?? 'Sale refund';
            $returnedBy = $data['returned_by'] ?? $sale->sold_by;

            $refundAmount = 0.0;
            foreach ($sale->items as $item) {
                $refundAmount += (float) $item->total_amount;
            }

            $saleReturn = SaleReturn::query()->create([
                'sale_id' => $sale->id,
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'reference_no' => $data['reference_no'] ?? 'RET-'.now()->format('YmdHis'),
                'reason' => $reason,
                'refund_amount' => number_format($refundAmount, 4, '.', ''),
                'refund_method_id' => $data['refund_method_id'] ?? null,
                'status' => 'completed',
                'returned_by' => $returnedBy,
                'returned_at' => now(),
            ]);

            foreach ($sale->items as $item) {
                SaleReturnItem::query()->create([
                    'sale_return_id' => $saleReturn->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id ?? null,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount ?? '0.0000',
                    'tax_amount' => $item->tax_amount ?? '0.0000',
                    'line_total' => $item->total_amount,
                ]);

                $inventory = Inventory::query()->firstOrCreate([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $item->product_id,
                ], [
                    'quantity' => 0,
                ]);

                $inventory->quantity = (float) $inventory->quantity + (float) $item->quantity;
                $inventory->save();

                InventoryTransaction::query()->create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id ?? null,
                    'reference_type' => 'SaleReturn',
                    'reference_id' => $saleReturn->id,
                    'type' => 'SaleReturn',
                    'quantity' => (string) $item->quantity,
                    'unit_cost' => $item->cost_price ?? null,
                    'note' => $reason,
                    'created_by' => $returnedBy,
                ]);
            }

            $sale->status = (float) $sale->total_amount === $refundAmount ? 'refunded' : 'partially_refunded';
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
}
