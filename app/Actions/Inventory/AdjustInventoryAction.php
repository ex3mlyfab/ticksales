<?php

namespace App\Actions\Inventory;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Support\Facades\DB;

class AdjustInventoryAction
{
    /**
     * Apply a stock quantity change to a single product/variant at a branch and log it.
     * This static form is used by stock adjustment and stock transfer posting.
     */
    public static function handle(
        string $tenantId,
        string $branchId,
        string $productId,
        ?string $variantId = null,
        string|float|int $quantityChanged = 0,
        string $referenceType = 'stock_adjustment',
        ?string $referenceId = null,
        string $type = 'adjustment',
        ?string $note = null,
        string|float|int|null $unitCost = null,
        ?int $createdBy = null,
    ): void {
        DB::transaction(function () use ($tenantId, $branchId, $productId, $variantId, $quantityChanged, $referenceType, $referenceId, $type, $note, $unitCost, $createdBy) {
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

            $inventory->increment('quantity', $quantityChanged);

            InventoryTransaction::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantityChanged,
                'unit_cost' => $unitCost,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Adjust inventory for a single product within a branch.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];
            $branchId = $data['branch_id'];
            $productId = $data['product_id'];
            $quantityDelta = (float) ($data['quantity_delta'] ?? 0);
            $referenceType = $data['reference_type'] ?? 'StockAdjustment';
            $referenceId = $data['reference_id'] ?? null;
            $type = $data['type'] ?? 'Adjustment';
            $note = $data['note'] ?? null;

            $inventory = Inventory::query()->firstOrCreate([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $data['variant_id'] ?? null,
            ], [
                'quantity' => 0,
            ]);

            $quantityBefore = (float) $inventory->quantity;
            $quantityAfter = $quantityBefore + $quantityDelta;

            $adjustment = StockAdjustment::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'reference_no' => $data['reference_no'] ?? 'ADJ-'.now()->format('YmdHis'),
                'reason' => $note,
                'status' => 'completed',
                'adjusted_by' => $data['adjusted_by'] ?? null,
                'adjusted_at' => now(),
            ]);

            StockAdjustmentItem::query()->create([
                'adjustment_id' => $adjustment->id,
                'product_id' => $productId,
                'variant_id' => $data['variant_id'] ?? null,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'quantity_adjusted' => $quantityDelta,
                'unit_cost' => $data['unit_cost'] ?? null,
            ]);

            $inventory->quantity = $quantityAfter;
            $inventory->save();

            InventoryTransaction::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $data['variant_id'] ?? null,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId ?? $adjustment->id,
                'type' => $type,
                'quantity' => (string) $quantityDelta,
                'unit_cost' => $data['unit_cost'] ?? null,
                'note' => $note,
                'created_by' => $data['adjusted_by'] ?? null,
            ]);

            return $adjustment;
        });
    }
}
