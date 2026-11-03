<?php

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Inventory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected from financial routes', function () {
    get('/reports/profit-loss')->assertRedirect('/login');
    get('/reports/balance-sheet')->assertRedirect('/login');
});

test('profit and loss report renders net revenue, cogs, gross profit and net profit', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $otherBranch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    // Branch A: one sale of 110 (2 units at 50, cost 30 each), one 22 refund
    // and an 8 expense -> net revenue 88, cogs 60, gross 28, net profit 20.
    $sale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'reference_no' => 'SL-FIN-001',
        'idempotency_key' => 'fin-001',
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

    SaleReturn::query()->create([
        'sale_id' => $sale->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'reference_no' => 'RET-FIN-001',
        'reason' => 'Damaged',
        'refund_amount' => 22,
        'status' => 'completed',
        'returned_by' => $user->id,
        'returned_at' => now(),
    ]);

    Expense::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'expense_category_id' => $category->id,
        'description' => 'Rent',
        'amount' => 8,
        'expense_date' => today()->toDateString(),
        'recorded_by' => $user->id,
    ]);

    // Branch B: a 500 sale with 100 cost must not leak into Branch A filters.
    $branchBSale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $otherBranch->id,
        'reference_no' => 'SL-FIN-002',
        'idempotency_key' => 'fin-002',
        'status' => 'completed',
        'subtotal' => 500,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 500,
        'paid_amount' => 500,
        'change_amount' => 0,
        'outstanding_amount' => 0,
        'sold_by' => $user->id,
        'sold_at' => now(),
    ]);

    SaleItem::query()->create([
        'sale_id' => $branchBSale->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'quantity' => 1,
        'unit_price' => 500,
        'cost_price' => 100,
        'tax_rate' => 0,
        'discount_amount' => 0,
        'total_amount' => 500,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/reports/profit-loss')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/profit-loss')
            ->where('summary.revenue', '610.0000')
            ->where('summary.net_revenue', '588.0000')
            ->where('summary.cogs', '160.0000')
            ->where('summary.gross_profit', '428.0000')
            ->where('summary.operating_expenses', '8.0000')
            ->where('summary.net_profit', '420.0000')
            ->where('summary.sales_count', 2)
            ->has('summary.expenses_by_category', 1));

    // Scoped to Branch A only.
    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/reports/profit-loss?branch_id='.$branch->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/profit-loss')
            ->where('summary.revenue', '110.0000')
            ->where('summary.returns_amount', '22.0000')
            ->where('summary.net_revenue', '88.0000')
            ->where('summary.cogs', '60.0000')
            ->where('summary.gross_profit', '28.0000')
            ->where('summary.net_profit', '20.0000')
            ->where('summary.sales_count', 1));
});

test('profit and loss report is tenant scoped', function () {
    $tenantA = Tenant::factory()->create();
    $branchA = Branch::factory()->create(['tenant_id' => $tenantA->id]);
    $tenantB = Tenant::factory()->create();
    $branchB = Branch::factory()->create(['tenant_id' => $tenantB->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenantA);

    $productA = Product::factory()->create(['tenant_id' => $tenantA->id]);
    $productB = Product::factory()->create(['tenant_id' => $tenantB->id]);

    Sale::query()->create([
        'tenant_id' => $tenantA->id,
        'branch_id' => $branchA->id,
        'reference_no' => 'SL-ISO-A',
        'idempotency_key' => 'iso-a',
        'status' => 'completed',
        'subtotal' => 120,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 120,
        'paid_amount' => 120,
        'change_amount' => 0,
        'outstanding_amount' => 0,
        'sold_by' => $user->id,
        'sold_at' => now(),
    ]);

    Sale::query()->create([
        'tenant_id' => $tenantB->id,
        'branch_id' => $branchB->id,
        'reference_no' => 'SL-ISO-B',
        'idempotency_key' => 'iso-b',
        'status' => 'completed',
        'subtotal' => 5000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 5000,
        'paid_amount' => 5000,
        'change_amount' => 0,
        'outstanding_amount' => 0,
        'sold_by' => $user->id,
        'sold_at' => now(),
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->get('/reports/profit-loss')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/profit-loss')
            ->where('summary.revenue', '120.0000')
            ->where('summary.sales_count', 1));
});

test('balance sheet report renders assets, liabilities and implied equity', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    // Closed register session contributes 620 cash; an open session opened
    // with 100 plus a 50 cash-in movement contributes 150 more.
    $register = Register::factory()->create(['tenant_id' => $tenant->id, 'branch_id' => $branch->id]);

    RegisterSession::query()->create([
        'register_id' => $register->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'opened_by' => $user->id,
        'closing_balance' => 620,
        'expected_balance' => 620,
        'actual_balance' => 620,
        'variance' => 0,
        'status' => 'closed',
        'opened_at' => now()->subHours(8),
        'closed_at' => now(),
    ]);

    $openSession = RegisterSession::query()->create([
        'register_id' => $register->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'opened_by' => $user->id,
        'opening_balance' => 100,
        'status' => 'open',
        'opened_at' => now()->subHours(2),
    ]);

    CashMovement::query()->create([
        'register_session_id' => $openSession->id,
        'tenant_id' => $tenant->id,
        'type' => 'in',
        'amount' => 50,
        'recorded_by' => $user->id,
    ]);

    // One sale with 40 outstanding.
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'cost_price' => 10]);
    $paymentMethod = PaymentMethod::factory()->create(['tenant_id' => $tenant->id]);
    $sale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'reference_no' => 'SL-FIN-BS',
        'idempotency_key' => 'fin-bs',
        'status' => 'completed',
        'subtotal' => 240,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 240,
        'paid_amount' => 200,
        'change_amount' => 0,
        'outstanding_amount' => 40,
        'sold_by' => $user->id,
        'sold_at' => now(),
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'quantity' => 1,
        'unit_price' => 240,
        'cost_price' => 100,
        'tax_rate' => 0,
        'discount_amount' => 0,
        'total_amount' => 240,
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    // A purchase of 100 with 40 paid leaves 60 payable.
    Purchase::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => Supplier::factory()->create(['tenant_id' => $tenant->id])->id,
        'reference_no' => 'PO-FIN-BS',
        'status' => 'completed',
        'purchase_date' => today()->toDateString(),
        'subtotal' => 100,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 100,
        'paid_amount' => 40,
    ]);

    // No expenses for this tenant, so retained earnings are the P&L net:
    // revenue 240 - cogs 100 = 140.
    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/reports/balance-sheet')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/balance-sheet')
            ->where('summary.cash_on_hand', '770.0000')
            ->where('summary.accounts_receivable', '40.0000')
            ->where('summary.inventory_value', '30.0000')
            ->where('summary.total_assets', '840.0000')
            ->where('summary.accounts_payable', '60.0000')
            ->where('summary.total_liabilities', '60.0000')
            ->where('summary.retained_earnings', '140.0000')
            ->where('summary.owner_capital', '640.0000')
            ->where('summary.total_equity', '780.0000'));
});
