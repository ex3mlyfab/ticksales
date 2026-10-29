<?php

namespace App\Actions\Purchasing;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

class ReceivePurchaseAction
{
    /**
     * Execute the action to receive a purchase and update inventory.
     */
    public static function execute(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];
            $branchId = $data['branch_id'];
            $supplierId = $data['supplier_id'];
            $purchaseOrderId = $data['purchase_order_id'] ?? null;
            $purchaseDate = $data['purchase_date'] ?? now()->toDateString();
            $invoiceNo = $data['invoice_no'] ?? null;
            $paidAmount = $data['paid_amount'] ?? 0;
            $notes = $data['notes'] ?? null;
            $items = $data['items'];

            $subtotal = 0;
            $taxAmount = 0;
            $discountAmount = 0;

            // 1. Create the Purchase record
            $purchase = Purchase::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'purchase_order_id' => $purchaseOrderId,
                'reference_no' => 'PUR-'.now()->format('Y-m-d').'-'.str_pad((string) Purchase::where('tenant_id', $tenantId)->count(), 5, '0', STR_PAD_LEFT),
                'invoice_no' => $invoiceNo,
                'status' => 'received',
                'purchase_date' => $purchaseDate,
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total' => 0,
                'paid_amount' => $paidAmount,
                'notes' => $notes,
            ]);

            // 2. Process items and update inventory
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $variantId = $item['variant_id'] ?? null;
                $quantity = $item['quantity'];
                $unitCost = $item['unit_cost'];
                $taxRate = $item['tax_rate'] ?? 0;
                $discount = $item['discount_amount'] ?? 0;

                $lineSubtotal = $quantity * $unitCost;
                $lineDiscount = $discount;
                $lineTaxable = $lineSubtotal - $lineDiscount;
                $lineTax = $lineTaxable * ($taxRate / 100);
                $lineTotal = $lineTaxable + $lineTax;

                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
                $discountAmount += $lineDiscount;

                // Create Purchase Item
                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'discount_amount' => $discount,
                    'line_total' => $lineTotal,
                ]);

                // Update Inventory stock
                $inventory = Inventory::query()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'branch_id' => $branchId,
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                    ],
                    ['outstanding_balance' => 0, 'stock_level' => 0]
                );

                // Lock for update to prevent race conditions
                $inventory->lockForUpdate();
                $inventory->increment('stock_level', $quantity);
                $inventory->save();

                // Log Inventory Transaction
                InventoryTransaction::query()->create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'reference_type' => Purchase::class,
                    'notes' => 'Stock received from purchase '.$purchase->reference_no,
                ]);
            }

            // 3. Update Purchase totals
            $purchase->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $subtotal + $taxAmount - $discountAmount,
            ]);

            return $purchase;
        });
    }
}
