<?php

use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
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

test('tenant users can view their product with variants but not another tenants product', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Coffee Beans',
        'has_variants' => true,
    ]);

    ProductVariant::factory()->create([
        'tenant_id' => $tenant->id,
        'product_id' => $product->id,
        'name' => 'Dark Roast',
        'sku' => 'COF-DARK',
    ]);

    ProductVariant::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'product_id' => $product->id,
        'name' => 'Foreign Variant',
        'sku' => 'FOREIGN-VARIANT',
    ]);

    $otherTenantProduct = Product::factory()->create();

    $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.name', 'Coffee Beans')
        ->assertJsonCount(1, 'data.variants')
        ->assertJsonPath('data.variants.0.name', 'Dark Roast');

    $this->getJson('/api/v1/products/'.$otherTenantProduct->id)
        ->assertNotFound();
});

test('tenant users can search products by name sku and barcode', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $nameMatch = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Green Tea',
    ]);
    ProductVariant::factory()->create([
        'tenant_id' => $tenant->id,
        'product_id' => $nameMatch->id,
        'name' => 'Green Tea Large',
        'sku' => 'VAR-GREEN-01',
    ]);
    $skuMatch = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Loose Leaf Tea',
        'sku' => 'TEA-LOOSE-01',
    ]);
    $barcodeMatch = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Jasmine Tea',
    ]);

    ProductBarcode::factory()->create([
        'tenant_id' => $tenant->id,
        'product_id' => $barcodeMatch->id,
        'barcode' => 'ALT-TEA-001',
    ]);

    Product::factory()->create([
        'name' => 'Other Tenant Green Tea',
        'sku' => 'TEA-LOOSE-01',
    ]);

    $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/products/search?search=Green')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $nameMatch->id);

    $this->getJson('/api/v1/products/search?search=TEA-LOOSE-01')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $skuMatch->id);

    $this->getJson('/api/v1/products/search?search=VAR-GREEN-01')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $nameMatch->id);

    $this->getJson('/api/v1/products/search?search=ALT-TEA-001')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $barcodeMatch->id);

    $this->getJson('/api/v1/products/search')
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('search');
});
