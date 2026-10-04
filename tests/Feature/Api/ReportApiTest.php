<?php

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->tenant = Tenant::factory()->create();
    $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);

    TenantUser::query()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);
});

test('tenant users can view sales summary report through the api', function () {
    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Coffee Beans',
        'sku' => 'COF-001',
        'track_inventory' => true,
    ]);

    $sale = Sale::query()->create([
        'tenant_id' => $this->tenant->id,
        'branch_id' => $this->branch->id,
        'reference_no' => 'SALE-REPORT-1',
        'idempotency_key' => 'report-sale-1',
        'status' => 'completed',
        'subtotal' => '25.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.0000',
        'total_amount' => '25.0000',
        'paid_amount' => '25.0000',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'sold_by' => $this->user->id,
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

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-ID' => $this->tenant->id])
        ->getJson('/api/v1/reports/sales-summary?from='.now()->subDay()->toDateString().'&to='.now()->addDay()->toDateString());

    $response->assertOk()
        ->assertJsonPath('data.total_sales', '25.0000')
        ->assertJsonPath('data.orders_count', 1)
        ->assertJsonPath('data.items_sold', '2.0000');
});

test('tenant users can view a register session summary through the api', function () {
    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Tea',
        'sku' => 'TEA-01',
        'track_inventory' => true,
    ]);

    $register = Register::query()->create([
        'tenant_id' => $this->tenant->id,
        'branch_id' => $this->branch->id,
        'name' => 'Counter 1',
        'is_active' => true,
    ]);

    $session = RegisterSession::query()->create([
        'tenant_id' => $this->tenant->id,
        'branch_id' => $this->branch->id,
        'register_id' => $register->id,
        'opened_by' => $this->user->id,
        'opening_balance' => '100.0000',
        'closing_balance' => '140.0000',
        'status' => 'closed',
        'opened_at' => now()->subHour(),
        'closed_at' => now(),
    ]);

    $sale = Sale::query()->create([
        'tenant_id' => $this->tenant->id,
        'branch_id' => $this->branch->id,
        'register_session_id' => $session->id,
        'reference_no' => 'SALE-SESSION-1',
        'idempotency_key' => 'session-report-sale',
        'status' => 'completed',
        'subtotal' => '40.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.0000',
        'total_amount' => '40.0000',
        'paid_amount' => '40.0000',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'sold_by' => $this->user->id,
        'sold_at' => now(),
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'quantity' => '4.0000',
        'unit_price' => '10.0000',
        'cost_price' => '5.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.0000',
        'tax_rate' => '0.0000',
        'total_amount' => '40.0000',
    ]);

    CashMovement::query()->create([
        'tenant_id' => $this->tenant->id,
        'register_session_id' => $session->id,
        'type' => 'cash_in',
        'amount' => '15.0000',
        'reason' => 'Float increase',
        'recorded_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-ID' => $this->tenant->id])
        ->getJson('/api/v1/reports/register-session/'.$session->id);

    $response->assertOk()
        ->assertJsonPath('data.sales_total', '40.0000')
        ->assertJsonPath('data.cash_in', '15.0000')
        ->assertJsonPath('data.closing_balance', '140.0000');
});
