<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\Tenant;

test('purchase returns can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $purchase = Purchase::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PUR-2201',
        'invoice_no' => 'INV-2201',
        'status' => 'completed',
        'purchase_date' => now()->toDateString(),
        'subtotal' => '1000.0000',
        'tax_amount' => '100.0000',
        'discount_amount' => '50.0000',
        'total' => '1050.0000',
        'paid_amount' => '1050.0000',
        'notes' => 'Initial purchase',
    ]);

    $purchaseReturn = PurchaseReturn::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'purchase_id' => $purchase->id,
        'reference_no' => 'PR-1001',
        'reason' => 'Damaged packaging',
        'return_date' => now()->toDateString(),
        'subtotal' => '280.0000',
        'tax_amount' => '28.0000',
        'discount_amount' => '0.0000',
        'total' => '308.0000',
        'status' => 'pending',
        'notes' => 'Returned damaged goods',
    ]);

    $purchaseReturn->items()->create([
        'product_id' => $product->id,
        'quantity' => '4.0000',
        'unit_cost' => '70.0000',
        'tax_rate' => '10.0000',
        'discount_amount' => '0.0000',
        'line_total' => '280.0000',
        'notes' => 'Damaged packaging on four units',
    ]);

    expect($purchaseReturn->tenant_id)->toBe($tenant->id)
        ->and($purchaseReturn->supplier_id)->toBe($supplier->id)
        ->and($purchaseReturn->reference_no)->toBe('PR-1001')
        ->and($purchaseReturn->items()->count())->toBe(1)
        ->and($purchaseReturn->items()->first()->product_id)->toBe($product->id)
        ->and($purchaseReturn->total)->toBe('308.0000');
});
