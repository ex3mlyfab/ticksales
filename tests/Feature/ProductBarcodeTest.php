<?php

use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Tenant;

test('products can store multiple unique barcodes', function () {
    $tenant = Tenant::factory()->create();
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'barcode' => 'BASE-123',
    ]);

    $barcode = ProductBarcode::query()->create([
        'tenant_id' => $tenant->id,
        'product_id' => $product->id,
        'barcode' => 'SCAN-999',
        'is_primary' => true,
    ]);

    expect($barcode->product_id)->toBe($product->id)
        ->and($barcode->barcode)->toBe('SCAN-999')
        ->and($product->barcodes()->count())->toBe(1);
});
