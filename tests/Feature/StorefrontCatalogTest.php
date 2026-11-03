<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use Inertia\Testing\AssertableInertia as Assert;

test('public storefront lists only active published products belonging to the tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Published product',
        'is_storefront_published' => true,
    ]);

    Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Unpublished product',
    ]);

    Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Inactive product',
        'is_active' => false,
        'is_storefront_published' => true,
    ]);

    Product::factory()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Other tenant product',
        'is_storefront_published' => true,
    ]);

    $this->get(route('storefront.show', $tenant->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/show')
            ->where('store.name', $tenant->name)
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Published product')
            ->missing('products.data.0.cost_price')
        );
});

test('public storefront product details expose only published products from that store', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Published product',
        'description' => 'A public product description',
        'is_storefront_published' => true,
    ]);
    ProductVariant::factory()->create([
        'tenant_id' => $tenant->id,
        'product_id' => $product->id,
        'name' => 'Small',
        'selling_price' => 12,
    ]);
    ProductVariant::factory()->create([
        'tenant_id' => $tenant->id,
        'product_id' => $product->id,
        'name' => 'Internal option',
        'is_active' => false,
    ]);
    $otherProduct = Product::factory()->create([
        'tenant_id' => $otherTenant->id,
        'is_storefront_published' => true,
    ]);

    $this->get(route('storefront.products.show', [$tenant->slug, $product->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->where('product.name', 'Published product')
            ->where('product.description', 'A public product description')
            ->missing('product.cost_price')
            ->has('product.variants', 1)
            ->where('product.variants.0.name', 'Small')
            ->missing('product.variants.0.cost_price')
            ->missing('store.email')
        );

    $this->get(route('storefront.products.show', [$tenant->slug, $otherProduct->id]))
        ->assertNotFound();
});

test('inactive stores and unpublished products are not publicly accessible', function () {
    $inactiveTenant = Tenant::factory()->create(['is_active' => false]);
    $tenant = Tenant::factory()->create();
    $unpublishedProduct = Product::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $this->get(route('storefront.show', $inactiveTenant->slug))
        ->assertNotFound();

    $this->get(route('storefront.products.show', [$tenant->slug, $unpublishedProduct->id]))
        ->assertNotFound();
});
