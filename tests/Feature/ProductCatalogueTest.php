<?php

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Tenant;

test('product catalogue entities can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $category = ProductCategory::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Beverages',
        'slug' => 'beverages',
        'description' => 'Cold drinks and juices',
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

    expect($category->tenant_id)->toBe($tenant->id)
        ->and($category->slug)->toBe('beverages')
        ->and($brand->name)->toBe('Northwind')
        ->and($unit->abbreviation)->toBe('btl');
});

test('branches and inventory records can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $branch = Branch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Downtown Store',
        'code' => 'DTW',
        'address' => '123 Main St',
        'phone' => '555-0101',
        'email' => 'downtown@example.com',
        'is_main' => true,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $inventory = Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 45,
    ]);

    expect($branch->tenant_id)->toBe($tenant->id)
        ->and($branch->code)->toBe('DTW')
        ->and($inventory->branch_id)->toBe($branch->id)
        ->and($inventory->product_id)->toBe($product->id)
        ->and((float) $inventory->quantity)->toBe(45.0);
});
