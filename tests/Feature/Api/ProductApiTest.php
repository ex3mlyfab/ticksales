<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('tenant users can list products through the api', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Espresso Beans',
        'sku' => 'ESP-001',
        'cost_price' => '12.5000',
        'selling_price' => '18.7500',
    ]);

    Product::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'name' => 'Other Brand Product',
        'sku' => 'OTHER-001',
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/products');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Espresso Beans')
        ->assertJsonPath('data.0.sku', 'ESP-001');
});

test('tenant users can list only their product categories through the api', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    ProductCategory::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Beverages',
        'slug' => 'beverages',
        'sort_order' => 2,
    ]);

    ProductCategory::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Bakery',
        'slug' => 'bakery',
        'sort_order' => 1,
    ]);

    ProductCategory::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'name' => 'Other Tenant Category',
        'slug' => 'other-tenant-category',
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/product-categories');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Bakery')
        ->assertJsonPath('data.0.slug', 'bakery')
        ->assertJsonPath('data.1.name', 'Beverages');
});
