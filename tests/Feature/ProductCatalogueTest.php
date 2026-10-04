<?php

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
