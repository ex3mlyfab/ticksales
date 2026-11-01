<?php

namespace App\Actions\Purchasing;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

class ReceivePurchaseAction
{
    public static function execute(array $data): Purchase
    {
        return DB::transaction(function () use ($data): Purchase {
            return array_key_exists('purchase_id', $data) && $data['purchase_id'] !== null && $data['purchase_id'] !== ''
                ? self::receiveExisting($data)
                : self::receiveNew($data);
        });
    }

    private static function receiveExisting(array $data): Purchase
    {
        $purchase = Purchase::query()->where('tenant_id', $data['tenant_id'] ?? '')->findOrFail($data['purchase_id']);
        $purchase->loadMissing('items');
        foreach ($purchase->items as $item) {
            self::addStock(tenantId: $purchase->tenant_id, branchId: $data['branch_id'] ?? $purchase->branch_id, productId: $item->product_id, variantId: $item->variant_id, quantity: $item->quantity, unitCost: $item->unit_cost, note: 'Stock received', reference: $purchase);
        }
        $purchase->update(['status' => 'completed']);

        return $purchase;
    }

    private static function receiveNew(array $data): Purchase
    {
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

        $purchase = Purchase::query()->create([
            'tenant_id' => $tenantId, 'branch_id' => $branchId, 'supplier_id' => $supplierId, 'purchase_order_id' => $purchaseOrderId,
            'reference_no' => 'PUR-'.now()->format('Y-m-d').'-'.str_pad((string) Purchase::where('tenant_id', $tenantId)->count(), 5, '0', STR_PAD_LEFT),
            'invoice_no' => $invoiceNo, 'status' => 'completed', 'purchase_date' => $purchaseDate,
            'subtotal' => 0, 'tax_amount' => 0, 'discount_amount' => 0, 'total' => 0, 'paid_amount' => $paidAmount, 'notes' => $notes,
        ]);

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
            PurchaseItem::query()->create(['purchase_id' => $purchase->id, 'product_id' => $productId, 'variant_id' => $variantId, 'quantity' => $quantity, 'unit_cost' => $unitCost, 'tax_rate' => $taxRate, 'discount_amount' => $discount, 'line_total' => $lineTotal]);
            self::addStock(tenantId: $tenantId, branchId: $branchId, productId: $productId, variantId: $variantId, quantity: $quantity, unitCost: $unitCost, note: 'Stock received', reference: $purchase);
        }

        $purchase->update(['subtotal' => $subtotal, 'tax_amount' => $taxAmount, 'discount_amount' => $discountAmount, 'total' => $subtotal + $taxAmount - $discountAmount]);

        return $purchase;
    }

    private static function addStock(string $tenantId, string $branchId, string $productId, ?string $variantId, string|float|int $quantity, string|float|int|null $unitCost = null, ?string $note = null, ?Purchase $reference = null): void
    {
        $inventory = Inventory::query()->where('tenant_id', $tenantId)->where('branch_id', $branchId)->where('product_id', $productId)->where('variant_id', $variantId)->lockForUpdate()->first();
        if (! $inventory) {
            $inventory = Inventory::query()->create(['tenant_id' => $tenantId, 'branch_id' => $branchId, 'product_id' => $productId, 'variant_id' => $variantId, 'quantity' => 0]);
        }
        $inventory->increment('quantity', $quantity);
        InventoryTransaction::query()->create(['tenant_id' => $tenantId, 'branch_id' => $branchId, 'product_id' => $productId, 'variant_id' => $variantId, 'quantity' => $quantity, 'unit_cost' => $unitCost, 'type' => 'purchase', 'reference_type' => Purchase::class, 'reference_id' => $reference?->id, 'note' => $note]);
    }
}
