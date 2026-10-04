<?php

namespace App\Actions\Inventory;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

class TransferStockAction
{
    /**
     * Transfer stock from one branch to another for a single product.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];
            $fromBranchId = $data['from_branch_id'];
            $toBranchId = $data['to_branch_id'];
            $productId = $data['product_id'];
            $quantity = (float) ($data['quantity'] ?? 0);

            $fromInventory = Inventory::query()->firstOrCreate([
                'tenant_id' => $tenantId,
                'branch_id' => $fromBranchId,
                'product_id' => $productId,
            ], [
                'quantity' => 0,
            ]);

            $toInventory = Inventory::query()->firstOrCreate([
                'tenant_id' => $tenantId,
                'branch_id' => $toBranchId,
                'product_id' => $productId,
            ], [
                'quantity' => 0,
            ]);

            $transfer = StockTransfer::query()->create([
                'tenant_id' => $tenantId,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'reference_no' => $data['reference_no'] ?? 'TRF-'.now()->format('YmdHis'),
                'status' => 'completed',
                'note' => $data['note'] ?? null,
                'transferred_by' => $data['transferred_by'] ?? null,
                'transferred_at' => now(),
            ]);

            StockTransferItem::query()->create([
                'transfer_id' => $transfer->id,
                'product_id' => $productId,
                'variant_id' => $data['variant_id'] ?? null,
                'quantity' => $quantity,
                'unit_cost' => $data['unit_cost'] ?? null,
            ]);

            $fromInventory->quantity = (float) $fromInventory->quantity - $quantity;
            $fromInventory->save();

            $toInventory->quantity = (float) $toInventory->quantity + $quantity;
            $toInventory->save();

            InventoryTransaction::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $fromBranchId,
                'product_id' => $productId,
                'variant_id' => $data['variant_id'] ?? null,
                'reference_type' => 'StockTransfer',
                'reference_id' => $transfer->id,
                'type' => 'TransferOut',
                'quantity' => (string) (-$quantity),
                'unit_cost' => $data['unit_cost'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['transferred_by'] ?? null,
            ]);

            InventoryTransaction::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $toBranchId,
                'product_id' => $productId,
                'variant_id' => $data['variant_id'] ?? null,
                'reference_type' => 'StockTransfer',
                'reference_id' => $transfer->id,
                'type' => 'TransferIn',
                'quantity' => (string) $quantity,
                'unit_cost' => $data['unit_cost'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['transferred_by'] ?? null,
            ]);

            return $transfer;
        });
    }
}
