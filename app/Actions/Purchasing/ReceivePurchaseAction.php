<?php

namespace App\Actions\Purchasing;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class ReceivePurchaseAction
{
    /**
     * Receive a purchase and update branch inventory from each purchase item.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $purchase = Purchase::query()->with('items')->findOrFail($data['purchase_id']);
            $tenantId = $data['tenant_id'] ?? $purchase->tenant_id;
            $branchId = $data['branch_id'] ?? $purchase->branch_id;

            foreach ($purchase->items as $item) {
                $inventory = Inventory::query()->firstOrCreate([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $item->product_id,
                ], [
                    'quantity' => 0,
                ]);

                $quantity = (float) $item->quantity;
                $inventory->quantity = (float) $inventory->quantity + $quantity;
                $inventory->save();

                InventoryTransaction::query()->create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id ?? null,
                    'reference_type' => 'Purchase',
                    'reference_id' => $purchase->id,
                    'type' => 'Purchase',
                    'quantity' => (string) $quantity,
                    'unit_cost' => $item->unit_cost,
                    'note' => $data['note'] ?? 'Purchase received',
                    'created_by' => $data['received_by'] ?? null,
                ]);
            }

            $purchase->status = 'completed';
            $purchase->save();

            return $purchase;
        });
    }
}
