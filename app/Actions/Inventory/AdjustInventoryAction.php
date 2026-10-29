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
