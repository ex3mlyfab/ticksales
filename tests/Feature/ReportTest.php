<?php

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Inventory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StorefrontAnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected from report routes', function () {
    get('/reports/sales')->assertRedirect('/login');
    get('/reports/inventory')->assertRedirect('/login');
    get('/reports/register-sessions')->assertRedirect('/login');
    get('/reports/expenses')->assertRedirect('/login');
    get('/reports/storefront-analytics')->assertRedirect('/login');
});

test('storefront analytics report is tenant scoped and aggregates funnel and product events', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);
    $otherProduct = Product::factory()->create(['tenant_id' => $otherTenant->id]);
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    foreach (['product_viewed', 'cart_item_added', 'checkout_started', 'order_paid'] as $event) {
        StorefrontAnalyticsEvent::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'event' => $event,
            'session_hash' => hash('sha256', "tenant-session-{$event}"),
        ]);
    }
    StorefrontAnalyticsEvent::query()->create([
        'tenant_id' => $otherTenant->id,
        'product_id' => $otherProduct->id,
        'event' => 'product_viewed',
        'session_hash' => hash('sha256', 'other-tenant-session'),
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/reports/storefront-analytics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/storefront-analytics')
            ->where('summary.sessions', 4)
            ->where('summary.events', 4)
            ->where('summary.paid_orders', 1)
            ->where('funnel.0.count', 1)
            ->where('funnel.3.count', 1)
            ->has('product_performance', 1)
            ->where('product_performance.0.product_id', $product->id));
});

test('sales report renders with summary and daily breakdown', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);
    $paymentMethod = PaymentMethod::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $sale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'reference_no' => 'SL-RPT-001',
        'idempotency_key' => 'rpt-001',
        'status' => 'completed',
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 110,
        'paid_amount' => 110,
        'change_amount' => 0,
        'outstanding_amount' => 0,
        'sold_by' => $user->id,
        'sold_at' => now(),
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'quantity' => 2,
        'unit_price' => 50,
        'cost_price' => 30,
        'tax_rate' => 10,
        'discount_amount' => 0,
        'total_amount' => 110,
    ]);

    SalePayment::query()->create([
        'sale_id' => $sale->id,
        'payment_method_id' => $paymentMethod->id,
        'amount' => 110,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/reports/sales')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/sales')
            ->where('summary.total_sales', '110.0000')
            ->where('summary.orders_count', 1)
            ->where('summary.items_sold', '2.0000')
            ->has('daily', 1)
            ->has('top_products', 1)
            ->has('payments', 1));
});

test('inventory report renders with stock value and low stock count', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'cost_price' => 10,
        'alert_quantity' => 5,
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/reports/inventory')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/inventory')
            ->where('summary.low_stock_count', 1)
            ->where('summary.total_value', '30.0000')
            ->has('inventory.data', 1));
});

test('register sessions report renders session rows', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $register = Register::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    RegisterSession::query()->create([
        'register_id' => $register->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'opened_by' => $user->id,
        'opening_balance' => 500,
        'closing_balance' => 620,
        'expected_balance' => 620,
        'actual_balance' => 620,
        'variance' => 0,
        'status' => 'closed',
        'opened_at' => now()->subHours(6),
        'closed_at' => now(),
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/reports/register-sessions')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/register-sessions')
            ->has('sessions.data', 1));
});

test('expenses report renders with total and category breakdown', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    Expense::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'expense_category_id' => $category->id,
        'description' => 'Rent',
        'amount' => 250,
        'expense_date' => now()->toDateString(),
        'recorded_by' => $user->id,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/reports/expenses')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/expenses')
            ->where('summary.total_amount', '250.0000')
            ->has('by_category', 1)
            ->has('expenses.data', 1));
});
