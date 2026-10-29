<?php

use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access product categories', function () {
    get('/product-categories')->assertRedirect('/login');
});

test('authenticated users can view product categories index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/product-categories')
        ->assertOk();
});

test('authenticated users can create a product category', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $response = actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/product-categories', [
            'tenant_id' => $tenant->id,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and gadgets',
            'sort_order' => 0,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('product-categories.index'));

    $category = ProductCategory::query()->where('name', 'Electronics')->first();

    expect($category)->not->toBeNull();
    expect($category->slug)->toBe('electronics');
    expect($category->description)->toBe('Electronic devices and gadgets');
    expect($category->tenant_id)->toBe($tenant->id);
});

test('authenticated users can update a product category', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $category = ProductCategory::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    $categoryId = $category->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/product-categories/'.$categoryId, [
            'tenant_id' => $tenant->id,
            'name' => 'New Name',
            'slug' => 'new-name',
            'description' => 'Updated description',
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('product-categories.index'));

    $category->refresh();

    expect($category->name)->toBe('New Name');
    expect($category->slug)->toBe('new-name');
    expect($category->description)->toBe('Updated description');
    expect($category->sort_order)->toBe(1);
});

test('authenticated users can delete a product category', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $category = ProductCategory::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $categoryId = $category->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/product-categories/'.$categoryId)
        ->assertRedirect(route('product-categories.index'));

    $foundCategory = ProductCategory::query()->withTrashed()->find($categoryId);

    expect($foundCategory)->not->toBeNull();
    expect($foundCategory->trashed())->toBeTrue();
});
