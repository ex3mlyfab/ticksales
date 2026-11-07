<?php

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Inertia\Testing\AssertableInertia as Assert;

test('public store directory lists only active storefront-enabled tenants with valid products', function () {
    $visible = Tenant::factory()->create(['name' => 'Visible Market']);
    TenantSetting::factory()->create([
        'tenant_id' => $visible->id,
        'is_storefront_enabled' => true,
        'storefront_pickup_enabled' => true,
        'storefront_delivery_enabled' => false,
        'storefront_description' => 'Fresh local goods',
    ]);
    Product::factory()->create([
        'tenant_id' => $visible->id,
        'name' => 'Visible product',
        'description' => 'A published item',
        'is_active' => true,
        'is_storefront_published' => true,
        'selling_price' => 12,
    ]);

    $inactive = Tenant::factory()->create([
        'name' => 'Inactive Market',
        'is_active' => false,
    ]);
    TenantSetting::factory()->create([
        'tenant_id' => $inactive->id,
        'is_storefront_enabled' => true,
    ]);
    Product::factory()->create([
        'tenant_id' => $inactive->id,
        'description' => 'A published item',
        'is_active' => true,
        'is_storefront_published' => true,
    ]);

    $disabled = Tenant::factory()->create(['name' => 'Disabled Market']);
    TenantSetting::factory()->create([
        'tenant_id' => $disabled->id,
        'is_storefront_enabled' => false,
    ]);
    Product::factory()->create([
        'tenant_id' => $disabled->id,
        'description' => 'A published item',
        'is_active' => true,
        'is_storefront_published' => true,
    ]);

    $this->get(route('storefront.directory'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/directory')
            ->where('stores.total', 1)
            ->where('stores.data.0.name', 'Visible Market')
            ->where('stores.data.0.product_count', 1)
            ->missing('stores.data.0.email')
        );

    $this->get(route('storefront.directory', ['q' => 'Fresh local']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stores.total', 1)
            ->where('stores.data.0.name', 'Visible Market')
        );

    $this->get(route('storefront.directory', ['fulfillment' => 'delivery']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stores.total', 0)
        );
});

test('marketplace filters products by category brand store search and price', function () {
    $tenant = Tenant::factory()->create(['name' => 'North Shop', 'currency' => 'USD']);
    $otherTenant = Tenant::factory()->create(['name' => 'Other Shop', 'currency' => 'USD']);
    TenantSetting::factory()->create([
        'tenant_id' => $tenant->id,
        'is_storefront_enabled' => true,
    ]);
    TenantSetting::factory()->create([
        'tenant_id' => $otherTenant->id,
        'is_storefront_enabled' => true,
    ]);

    $category = ProductCategory::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pantry',
        'is_active' => true,
    ]);
    $brand = ProductBrand::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Harbor Goods',
    ]);

    $matching = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'name' => 'Sea Salt Biscuits',
        'description' => 'Crisp pantry snack',
        'selling_price' => 8.25,
        'is_active' => true,
        'is_storefront_published' => true,
    ]);
    $otherCategory = ProductCategory::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Drinks',
        'is_active' => true,
    ]);
    Product::factory()->create([
        'tenant_id' => $tenant->id,
        'category_id' => $otherCategory->id,
        'name' => 'Sparkling Tea',
        'description' => 'Cold drink',
        'selling_price' => 4,
        'is_active' => true,
        'is_storefront_published' => true,
    ]);
    Product::factory()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Sea Salt Biscuits Other Store',
        'description' => 'Different stock',
        'selling_price' => 15,
        'is_active' => true,
        'is_storefront_published' => true,
    ]);

    $this->get(route('storefront.marketplace', [
        'q' => 'biscuits',
        'category' => $category->id,
        'brand' => $brand->id,
        'store' => $tenant->id,
        'min_price' => 8,
        'max_price' => 9,
        'sort' => 'price_low',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/marketplace')
            ->where('products.total', 1)
            ->where('products.data.0.id', $matching->id)
            ->where('products.data.0.store.slug', $tenant->slug)
            ->missing('products.data.0.cost_price')
        );
});

test('marketplace hides products from inactive or storefront-disabled tenants', function () {
    $inactive = Tenant::factory()->create(['is_active' => false]);
    TenantSetting::factory()->create([
        'tenant_id' => $inactive->id,
        'is_storefront_enabled' => true,
    ]);
    Product::factory()->create([
        'tenant_id' => $inactive->id,
        'name' => 'Hidden inactive product',
        'description' => 'Should not be public',
        'is_active' => true,
        'is_storefront_published' => true,
    ]);

    $disabled = Tenant::factory()->create();
    TenantSetting::factory()->create([
        'tenant_id' => $disabled->id,
        'is_storefront_enabled' => false,
    ]);
    Product::factory()->create([
        'tenant_id' => $disabled->id,
        'name' => 'Hidden disabled product',
        'description' => 'Should not be public',
        'is_active' => true,
        'is_storefront_published' => true,
    ]);

    $visible = Tenant::factory()->create();
    TenantSetting::factory()->create([
        'tenant_id' => $visible->id,
        'is_storefront_enabled' => true,
    ]);
    Product::factory()->create([
        'tenant_id' => $visible->id,
        'name' => 'Visible product',
        'description' => 'Public catalog item',
        'is_active' => true,
        'is_storefront_published' => true,
    ]);

    $this->get(route('storefront.marketplace'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/marketplace')
            ->where('products.total', 1)
            ->where('products.data.0.name', 'Visible product')
        );
});
