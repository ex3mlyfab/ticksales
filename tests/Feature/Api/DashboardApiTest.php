<?php

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('tenant users can view dashboard metrics through the api', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Downtown Store',
        'is_active' => true,
    ]);

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
        'sku' => 'COF-001',
        'alert_quantity' => 10,
        'track_inventory' => true,
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => '4.0000',
    ]);

    $sale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'reference_no' => 'SALE-1001',
        'idempotency_key' => 'sale-kpi-1001',
        'status' => 'completed',
        'subtotal' => '25.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.0000',
        'total_amount' => '25.0000',
        'paid_amount' => '25.0000',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'sold_by' => $user->id,
        'sold_at' => now(),
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'quantity' => '2.0000',
        'unit_price' => '12.5000',
        'cost_price' => '7.5000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.0000',
        'tax_rate' => '0.0000',
        'total_amount' => '25.0000',
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/dashboard');

    $response->assertOk()
        ->assertJsonPath('data.stats.total_sales_today', '25.0000')
        ->assertJsonPath('data.stats.orders_today', 1)
        ->assertJsonPath('data.low_stock.0.name', 'Coffee Beans')
        ->assertJsonPath('data.low_stock.0.quantity', '4.0000');
});
