<?php

use App\Models\ProductBrand;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access product brands', function () {
    get('/product-brands')->assertRedirect('/login');
});

test('authenticated users can view product brands index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/product-brands')
        ->assertStatus(200);
});

test('authenticated users can create a product brand', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $response = actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/product-brands', [
            'tenant_id' => $tenant->id,
            'name' => 'Electronics',
        ]);

    $response->assertRedirect(route('product-brands.index'));

    $brand = ProductBrand::query()->where('name', 'Electronics')->first();

    expect($brand)->not->toBeNull();
    expect($brand->name)->toBe('Electronics');
    expect($brand->tenant_id)->toBe($tenant->id);
});

test('authenticated users can update a product brand', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $brand = ProductBrand::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Brand',
    ]);

    $brandId = $brand->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/product-brands/'.$brandId, [
            'tenant_id' => $tenant->id,
            'name' => 'New Brand',
        ])
        ->assertRedirect(route('product-brands.index'));

    $brand->refresh();

    expect($brand->name)->toBe('New Brand');
});

test('authenticated users can delete a product brand', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $brand = ProductBrand::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $brandId = $brand->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/product-brands/'.$brandId)
        ->assertRedirect(route('product-brands.index'));

    $foundBrand = ProductBrand::query()->withTrashed()->find($brandId);

    expect($foundBrand)->not->toBeNull();
    expect($foundBrand->trashed())->toBeTrue();
});
