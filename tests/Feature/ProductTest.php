<?php

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\TaxRate;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access products', function () {
    get('/products')->assertRedirect('/login');
});

test('authenticated users can view products index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(route('products.index'))
        ->assertOk();
});

test('authenticated users can create a product', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);
    $brand = ProductBrand::factory()->create(['tenant_id' => $tenant->id]);
    $unit = ProductUnit::factory()->create(['tenant_id' => $tenant->id]);
    $taxRate = TaxRate::factory()->create(['tenant_id' => $tenant->id]);

    attachUserToTenant($user, $tenant);

    $response = actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/products', [
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'tax_rate_id' => $taxRate->id,
            'name' => 'Blue Widget',
            'sku' => 'BW-001',
            'barcode' => '1234567890123',
            'cost_price' => 10.5,
            'selling_price' => 19.99,
            'has_variants' => false,
            'track_inventory' => true,
            'alert_quantity' => 5,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('products.index'));

    $product = Product::query()->where('name', 'Blue Widget')->first();

    expect($product)->not->toBeNull();
    expect($product->tenant_id)->toBe($tenant->id);
    expect($product->category_id)->toBe($category->id);
    expect($product->brand_id)->toBe($brand->id);
    expect($product->unit_id)->toBe($unit->id);
    expect($product->tax_rate_id)->toBe($taxRate->id);
    expect($product->sku)->toBe('BW-001');
    expect($product->selling_price)->toBe('19.9900');
    expect($product->track_inventory)->toBeTrue();
    expect($product->alert_quantity)->toBe(5);
    expect($product->is_active)->toBeTrue();
});

test('authenticated users can update a product', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['tenant_id' => $tenant->id]);

    attachUserToTenant($user, $tenant);

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Product',
        'sku' => 'OLD-001',
    ]);

    $productId = $product->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/products/'.$productId, [
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'New Product',
            'sku' => 'NEW-001',
            'cost_price' => 12,
            'selling_price' => 24,
            'has_variants' => false,
            'track_inventory' => true,
            'alert_quantity' => 3,
            'is_active' => true,
        ])
        ->assertRedirect(route('products.index'));

    $product->refresh();

    expect($product->name)->toBe('New Product');
    expect($product->sku)->toBe('NEW-001');
    expect($product->category_id)->toBe($category->id);
    expect($product->selling_price)->toBe('24.0000');
    expect($product->alert_quantity)->toBe(3);
});

test('authenticated users can delete a product', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $productId = $product->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/products/'.$productId)
        ->assertRedirect(route('products.index'));

    $foundProduct = Product::query()->withTrashed()->find($productId);

    expect($foundProduct)->not->toBeNull();
    expect($foundProduct->trashed())->toBeTrue();
});

test('users cannot access other tenants products', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherProduct = Product::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $otherProductId = $otherProduct->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/products/'.$otherProductId.'/edit')
        ->assertStatus(403);
});

test('users cannot update other tenants products', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherProduct = Product::factory()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Keep Me',
    ]);

    $otherProductId = $otherProduct->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/products/'.$otherProductId, [
            'tenant_id' => $tenant->id,
            'name' => 'Hijacked',
            'cost_price' => 1,
            'selling_price' => 2,
            'has_variants' => false,
            'track_inventory' => true,
            'alert_quantity' => 0,
            'is_active' => true,
        ])
        ->assertStatus(403);

    $otherProduct->refresh();

    expect($otherProduct->name)->toBe('Keep Me');
});

test('users cannot delete other tenants products', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherProduct = Product::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $otherProductId = $otherProduct->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/products/'.$otherProductId)
        ->assertStatus(403);

    expect(Product::query()->withTrashed()->find($otherProductId)?->trashed())->toBeFalse();
});
