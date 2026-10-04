<?php

use App\Actions\Purchasing\ReceivePurchaseAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;

test('purchase orders can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PO-1001',
        'status' => 'draft',
        'order_date' => now()->toDateString(),
        'expected_date' => now()->addDays(3)->toDateString(),
        'subtotal' => '1250.0000',
        'tax_amount' => '125.0000',
        'discount_amount' => '50.0000',
        'total' => '1325.0000',
        'notes' => 'Initial stock replenishment',
    ]);

    $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'quantity' => '10.0000',
        'unit_cost' => '110.0000',
        'tax_rate' => '10.0000',
        'discount_amount' => '0.0000',
        'line_total' => '1100.0000',
        'notes' => 'Restock for seasonal demand',
    ]);

    expect($purchaseOrder->tenant_id)->toBe($tenant->id)
        ->and($purchaseOrder->supplier_id)->toBe($supplier->id)
        ->and($purchaseOrder->reference_no)->toBe('PO-1001')
        ->and($purchaseOrder->items()->count())->toBe(1)
        ->and($purchaseOrder->items()->first()->product_id)->toBe($product->id)
        ->and($purchaseOrder->total)->toBe('1325.0000');
});

test('purchases can be created for a tenant with items', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $purchase = Purchase::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PUR-2001',
        'invoice_no' => 'INV-2001',
        'status' => 'completed',
        'purchase_date' => now()->toDateString(),
        'subtotal' => '2200.0000',
        'tax_amount' => '220.0000',
        'discount_amount' => '100.0000',
        'total' => '2320.0000',
        'paid_amount' => '2320.0000',
        'notes' => 'Warehouse restock',
    ]);

    $purchase->items()->create([
        'product_id' => $product->id,
        'quantity' => '20.0000',
        'unit_cost' => '110.0000',
        'tax_rate' => '10.0000',
        'discount_amount' => '5.0000',
        'line_total' => '2195.0000',
        'notes' => 'Seasonal restock',
    ]);

    expect($purchase->tenant_id)->toBe($tenant->id)
        ->and($purchase->supplier_id)->toBe($supplier->id)
        ->and($purchase->reference_no)->toBe('PUR-2001')
        ->and($purchase->items()->count())->toBe(1)
        ->and($purchase->items()->first()->product_id)->toBe($product->id)
        ->and($purchase->total)->toBe('2320.0000');
});

test('receive purchase action updates branch inventory and ledger', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $purchase = Purchase::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PUR-RECV-1',
        'invoice_no' => 'INV-RECV-1',
        'status' => 'pending',
        'purchase_date' => now()->toDateString(),
        'subtotal' => '500.0000',
        'tax_amount' => '50.0000',
        'discount_amount' => '0.0000',
        'total' => '550.0000',
        'paid_amount' => '0.0000',
        'notes' => 'Receiving stock after vendor fulfillment',
    ]);

    $purchase->items()->create([
        'product_id' => $product->id,
        'quantity' => '15.0000',
        'unit_cost' => '30.0000',
        'tax_rate' => '10.0000',
        'discount_amount' => '0.0000',
        'line_total' => '450.0000',
        'notes' => 'Stock received',
    ]);

    $action = new ReceivePurchaseAction;
    $receivedPurchase = $action->execute([
        'purchase_id' => $purchase->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
    ]);

    $inventory = Inventory::query()->where('tenant_id', $tenant->id)
        ->where('branch_id', $branch->id)
        ->where('product_id', $product->id)
        ->first();

    expect($receivedPurchase->status)->toBe('completed')
        ->and($inventory)->not->toBeNull()
        ->and((float) $inventory->quantity)->toBe(15.0)
        ->and($receivedPurchase->items()->count())->toBe(1);
});
