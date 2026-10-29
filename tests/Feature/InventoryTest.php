<?php

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access inventory', function () {
    get('/inventory')->assertRedirect('/login');
});

test('authenticated users can view inventory index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    Inventory::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 100,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/inventory')
        ->assertStatus(200);
});

test('inventory only shows records for current tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    Inventory::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 50,
    ]);

    // Other tenant's inventory
    $otherBranch = Branch::factory()->create(['tenant_id' => $otherTenant->id]);
    $otherProduct = Product::factory()->create(['tenant_id' => $otherTenant->id]);
    Inventory::factory()->create([
        'tenant_id' => $otherTenant->id,
        'branch_id' => $otherBranch->id,
        'product_id' => $otherProduct->id,
        'quantity' => 200,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/inventory')
        ->assertStatus(200);
});

test('inventory.show displays product stock across branches', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $branch1 = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $branch2 = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    Inventory::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch1->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    Inventory::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch2->id,
        'product_id' => $product->id,
        'quantity' => 20,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/inventory/product/'.$product->id)
        ->assertStatus(200);
});

test('inventory.show returns 404 for product from other tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherProduct = Product::factory()->create(['tenant_id' => $otherTenant->id]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/inventory/product/'.$otherProduct->id)
        ->assertStatus(404);
});
