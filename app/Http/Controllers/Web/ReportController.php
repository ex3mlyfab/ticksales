<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StorefrontAnalyticsEvent;
use App\Support\Financials\BranchBalanceSheet;
use App\Support\Financials\BranchProfitAndLoss;
use App\Support\StorefrontAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function sales(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $from = Carbon::parse($request->query('from', today()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', today()->toDateString()))->endOfDay();
        $branchId = $request->query('branch_id');

        $salesQuery = Sale::query()
            ->where('tenant_id', $tenant?->id)
            ->whereBetween('sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        $sales = $salesQuery->get();
        $ordersCount = $sales->count();
        $totalSales = (float) $sales->sum('total_amount');

        $itemsSold = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.tenant_id', $tenant?->id)
            ->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('sales.branch_id', $branchId))
            ->sum(DB::raw('CAST(sale_items.quantity AS DECIMAL(15,4))'));

        $daily = Sale::query()
            ->where('tenant_id', $tenant?->id)
            ->whereBetween('sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->select(
                DB::raw('DATE(sold_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(CAST(total_amount AS DECIMAL(15,4))) as total'),
            )
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('date')
            ->get();

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.tenant_id', $tenant?->id)
            ->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('sales.branch_id', $branchId))
            ->select(
                'products.name as name',
                DB::raw('SUM(CAST(sale_items.quantity AS DECIMAL(15,4))) as quantity'),
                DB::raw('SUM(CAST(sale_items.total_amount AS DECIMAL(15,4))) as revenue'),
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $payments = SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'sale_payments.payment_method_id')
            ->where('sales.tenant_id', $tenant?->id)
            ->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('sales.branch_id', $branchId))
            ->select(
                'payment_methods.name as name',
                DB::raw('SUM(CAST(sale_payments.amount AS DECIMAL(15,4))) as amount'),
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('amount')
            ->get();

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/sales', [
            'summary' => [
                'total_sales' => number_format($totalSales, 4, '.', ''),
                'orders_count' => $ordersCount,
                'items_sold' => number_format((float) $itemsSold, 4, '.', ''),
                'average_ticket' => number_format($ordersCount > 0 ? $totalSales / $ordersCount : 0, 4, '.', ''),
            ],
            'daily' => $daily->map(fn ($row) => [
                'date' => $row->date,
                'orders' => (int) $row->orders,
                'total' => number_format((float) $row->total, 4, '.', ''),
            ]),
            'top_products' => $topProducts->map(fn ($row) => [
                'name' => $row->name,
                'quantity' => number_format((float) $row->quantity, 4, '.', ''),
                'revenue' => number_format((float) $row->revenue, 4, '.', ''),
            ]),
            'payments' => $payments->map(fn ($row) => [
                'name' => $row->name,
                'amount' => number_format((float) $row->amount, 4, '.', ''),
            ]),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'branch_id' => $branchId,
            ],
            'branches' => $branches,
        ]);
    }

    public function storefrontAnalytics(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');
        $from = Carbon::parse($request->query('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', today()->toDateString()))->endOfDay();

        abort_if($tenant === null, 403);

        $events = StorefrontAnalyticsEvent::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->with('product:id,name')
            ->get(['event', 'session_hash', 'product_id']);

        $funnel = collect(StorefrontAnalytics::ALLOWED_EVENTS)
            ->mapWithKeys(function (string $event): array {
                return [$event => 0];
            });

        foreach ($events->groupBy('event') as $event => $rows) {
            if ($funnel->has($event)) {
                $funnel[$event] = $rows->pluck('session_hash')->unique()->count();
            }
        }

        $productPerformance = $events
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(function ($rows): array {
                $product = $rows->first()?->product;

                return [
                    'product_id' => $rows->first()->product_id,
                    'name' => $product?->name ?? __('Deleted product'),
                    'product_views' => $rows->where('event', 'product_viewed')->count(),
                    'cart_additions' => $rows->where('event', 'cart_item_added')->count(),
                    'cart_removals' => $rows->where('event', 'cart_item_removed')->count(),
                ];
            })
            ->values()
            ->sortByDesc('product_views')
            ->take(10)
            ->values();

        return Inertia::render('reports/storefront-analytics', [
            'enabled' => $tenant->tenantSetting?->is_storefront_analytics_enabled ?? false,
            'summary' => [
                'sessions' => $events->pluck('session_hash')->unique()->count(),
                'events' => $events->count(),
                'product_views' => $events->where('event', 'product_viewed')->count(),
                'checkout_starts' => $events->where('event', 'checkout_started')->count(),
                'paid_orders' => $events->where('event', 'order_paid')->count(),
            ],
            'funnel' => [
                ['name' => __('Product views'), 'key' => 'product_viewed', 'count' => $funnel['product_viewed']],
                ['name' => __('Cart additions'), 'key' => 'cart_item_added', 'count' => $funnel['cart_item_added']],
                ['name' => __('Checkouts started'), 'key' => 'checkout_started', 'count' => $funnel['checkout_started']],
                ['name' => __('Paid orders'), 'key' => 'order_paid', 'count' => $funnel['order_paid']],
            ],
            'product_performance' => $productPerformance,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'retention_days' => (int) config('storefront.analytics_event_retention_days', 90),
        ]);
    }

    public function inventory(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');
        $branchId = $request->query('branch_id');

        $inventory = Inventory::query()
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->join('branches', 'branches.id', '=', 'inventories.branch_id')
            ->where('inventories.tenant_id', $tenant?->id)
            ->when($branchId, fn ($query) => $query->where('inventories.branch_id', $branchId))
            ->select(
                'products.name as product_name',
                'products.sku',
                'products.alert_quantity',
                'branches.name as branch_name',
                'inventories.quantity',
                DB::raw('CAST(inventories.quantity * products.cost_price AS DECIMAL(15,4)) as stock_value'),
            )
            ->orderBy('products.name')
            ->paginate(50);

        $lowStockCount = Inventory::query()
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('inventories.tenant_id', $tenant?->id)
            ->when($branchId, fn ($query) => $query->where('inventories.branch_id', $branchId))
            ->whereRaw('inventories.quantity <= products.alert_quantity')
            ->count();

        $totalValue = Inventory::query()
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('inventories.tenant_id', $tenant?->id)
            ->when($branchId, fn ($query) => $query->where('inventories.branch_id', $branchId))
            ->sum(DB::raw('CAST(inventories.quantity * products.cost_price AS DECIMAL(15,4))'));

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/inventory', [
            'inventory' => $inventory,
            'summary' => [
                'low_stock_count' => $lowStockCount,
                'total_value' => number_format((float) $totalValue, 4, '.', ''),
            ],
            'filters' => [
                'branch_id' => $branchId,
            ],
            'branches' => $branches,
        ]);
    }

    public function registerSessions(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');
        $branchId = $request->query('branch_id');

        $sessions = RegisterSession::query()
            ->where('tenant_id', $tenant?->id)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with(['register', 'branch', 'openedBy', 'closedBy'])
            ->orderBy('opened_at', 'desc')
            ->paginate(20);

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/register-sessions', [
            'sessions' => $sessions,
            'filters' => [
                'branch_id' => $branchId,
            ],
            'branches' => $branches,
        ]);
    }

    public function expenses(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $from = Carbon::parse($request->query('from', today()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', today()->toDateString()))->endOfDay();
        $branchId = $request->query('branch_id');

        $expenses = Expense::query()
            ->where('tenant_id', $tenant?->id)
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with(['branch', 'expenseCategory', 'paymentMethod'])
            ->orderBy('expense_date', 'desc')
            ->paginate(20);

        $totalAmount = Expense::query()
            ->where('tenant_id', $tenant?->id)
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->sum('amount');

        $byCategory = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->where('expenses.tenant_id', $tenant?->id)
            ->whereBetween('expenses.expense_date', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('expenses.branch_id', $branchId))
            ->select(
                'expense_categories.name as name',
                DB::raw('SUM(CAST(expenses.amount AS DECIMAL(15,4))) as amount'),
            )
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('amount')
            ->get();

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/expenses', [
            'expenses' => $expenses,
            'summary' => [
                'total_amount' => number_format((float) $totalAmount, 4, '.', ''),
                'count' => $expenses->total(),
            ],
            'by_category' => $byCategory->map(fn ($row) => [
                'name' => $row->name,
                'amount' => number_format((float) $row->amount, 4, '.', ''),
            ]),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'branch_id' => $branchId,
            ],
            'branches' => $branches,
        ]);
    }

    /**
     * Profit and loss per tenant store (or consolidated) over a date range.
     */
    public function profitLoss(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $from = Carbon::parse($request->query('from', today()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', today()->toDateString()))->endOfDay();
        $branchId = $request->query('branch_id');

        $pnl = BranchProfitAndLoss::forPeriod($tenant, $branchId, $from, $to);

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/profit-loss', [
            'summary' => $pnl,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'branch_id' => $branchId,
            ],
            'branches' => $branches,
        ]);
    }

    /**
     * Simplified balance sheet per tenant store (or consolidated) as of a date.
     */
    public function balanceSheet(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $asOf = Carbon::parse($request->query('as_of', today()->toDateString()))->endOfDay();
        $branchId = $request->query('branch_id');

        $snapshot = BranchBalanceSheet::asOf($tenant, $branchId, $asOf);

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('reports/balance-sheet', [
            'summary' => $snapshot,
            'filters' => [
                'as_of' => $asOf->toDateString(),
                'branch_id' => $branchId,
            ],
            'branches' => $branches,
        ]);
    }
}
