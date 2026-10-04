<?php

use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\TaxRate;
use App\Models\Tenant;

test('tax rates, discounts, and products can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();
    $category = ProductCategory::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Beverages',
        'slug' => 'beverages',
        'is_active' => true,
    ]);
    $brand = ProductBrand::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Northwind',
    ]);
    $unit = ProductUnit::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Bottle',
        'abbreviation' => 'btl',
    ]);
    $taxRate = TaxRate::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'VAT 20%',
        'rate' => '20.0000',
        'is_inclusive' => false,
        'is_active' => true,
    ]);
    $discount = Discount::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Weekend deal',
        'type' => 'percentage',
        'value' => '10.0000',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'tax_rate_id' => $taxRate->id,
        'name' => 'Sparkling Water',
        'sku' => 'SW-100',
        'barcode' => '0123456789012',
        'cost_price' => '1.25',
        'selling_price' => '3.50',
        'track_inventory' => true,
        'alert_quantity' => 10,
        'is_active' => true,
    ]);

    expect($taxRate->tenant_id)->toBe($tenant->id)
        ->and($discount->type)->toBe('percentage')
        ->and($product->name)->toBe('Sparkling Water')
        ->and($product->taxRate->id)->toBe($taxRate->id);
});
