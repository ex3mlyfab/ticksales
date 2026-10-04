<?php

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard shows sales and inventory KPI data for the active tenant', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Downtown Store',
        'code' => 'DTW',
        'is_main' => true,
        'is_active' => true,
    ]);
    $user = User::factory()->create();

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

    session([
        'active_tenant_id' => $tenant->id,
        'active_branch_id' => $branch->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('stats.total_sales_today', '25.0000')
        ->where('stats.orders_today', 1)
        ->where('lowStock.0.name', 'Coffee Beans')
        ->where('lowStock.0.quantity', '4.0000')
    );
});
