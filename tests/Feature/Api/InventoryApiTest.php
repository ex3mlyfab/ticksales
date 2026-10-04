<?php

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('tenant users can list inventory snapshots through the api', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Espresso Beans',
        'sku' => 'ESP-001',
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '12.5000',
    ]);

    Inventory::query()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'branch_id' => Branch::factory()->create(['tenant_id' => $tenant->id])->id,
        'product_id' => Product::factory()->create(['tenant_id' => $tenant->id])->id,
        'quantity' => '99.0000',
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/inventory');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product_name', 'Espresso Beans')
        ->assertJsonPath('data.0.branch_name', $branch->name)
        ->assertJsonPath('data.0.quantity', '12.5000');
});
