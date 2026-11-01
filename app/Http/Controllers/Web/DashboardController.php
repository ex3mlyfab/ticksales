<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the dashboard for the active tenant.
     */
    public function index(): Response
    {
        // The active tenant is always the user's own tenant: SetTenantContext
        // only ever resolves tenants the user belongs to.
        $tenant = app(TenantContext::class)->getTenant();

        $branchId = $tenant !== null ? $this->resolveBranchId(request(), $tenant) : null;

        return $this->renderDashboard($tenant, $branchId);
    }

    /**
     * Show the dashboard for an explicitly requested tenant.
     *
     * This is the per-tenant entry point: a user lands on the dashboard of the
     * tenant they chose (URL `/t/{tenant}/dashboard`), which also becomes the
     * active tenant for the rest of the session.
     */
    public function show(Request $request, Tenant $tenant): Response
    {
        $user = $request->user();

        $isPlatformAdmin = (bool) $user?->is_platform_admin;
        $isActiveMember = (bool) $user?->tenants()
            ->wherePivot('is_active', true)
            ->whereKey($tenant->id)
            ->exists();

        // A non-member must not learn the tenant exists.
        abort_unless($isPlatformAdmin || $isActiveMember, 404);

        $request->session()->put('active_tenant_id', $tenant->id);

        $branchId = $this->resolveBranchId($request, $tenant);

        return $this->renderDashboard($tenant, $branchId);
    }

    /**
     * Render the dashboard for the given tenant and branch.
     */
    protected function renderDashboard(?Tenant $tenant, ?string $branchId): Response
    {
        $stats = $this->buildStats($tenant, $branchId);
        $topProducts = $this->buildTopProducts($tenant, $branchId);
        $lowStock = $this->buildLowStock($tenant, $branchId);

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'topProducts' => $topProducts,
            'lowStock' => $lowStock,
        ]);
    }

    /**
     * Resolve the branch to scope the dashboard to, preferring the session
     * branch when it belongs to the tenant and otherwise falling back to the
     * tenant's main branch.
     */
    protected function resolveBranchId(Request $request, Tenant $tenant): ?string
    {
        $branchId = $request->session()->get('active_branch_id');

        if ($branchId !== null && Branch::query()->where('tenant_id', $tenant->id)->whereKey($branchId)->exists()) {
            return $branchId;
        }

        $branch = Branch::query()->where('tenant_id', $tenant->id)->where('is_main', true)->first()
            ?? Branch::query()->where('tenant_id', $tenant->id)->first();

        if ($branch !== null) {
            $request->session()->put('active_branch_id', $branch->id);

            return $branch->id;
        }

        return null;
    }

    /**
     * Build summary KPIs for the current tenant and branch.
     *
     * @return array<string, mixed>
     */
    protected function buildStats(?Tenant $tenant, ?string $branchId): array
    {
        if ($tenant === null) {
            return [
                'total_sales_today' => '0.0000',
                'orders_today' => 0,
                'items_sold_today' => '0.0000',
                'average_ticket' => '0.0000',
                'inventory_alerts' => 0,
            ];
        }

        $todaySales = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->whereDate('sold_at', today())
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->get();

        $totalSalesToday = $todaySales->sum('total_amount');
        $ordersToday = $todaySales->count();

        $itemsSoldToday = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.tenant_id', $tenant->id)
            ->whereDate('sales.sold_at', today())
            ->when($branchId, fn ($query) => $query->where('sales.branch_id', $branchId))
            ->sum('sale_items.quantity');

        $lowStock = $this->buildLowStock($tenant, $branchId);

        return [
            'total_sales_today' => number_format((float) $totalSalesToday, 4, '.', ''),
            'orders_today' => $ordersToday,
            'items_sold_today' => number_format((float) $itemsSoldToday, 4, '.', ''),
            'average_ticket' => number_format($ordersToday > 0 ? ((float) $totalSalesToday / $ordersToday) : 0, 4, '.', ''),
            'inventory_alerts' => count($lowStock),
        ];
    }

    /**
     * Build top-selling products for the current tenant and branch.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildTopProducts(?Tenant $tenant, ?string $branchId): array
    {
        if ($tenant === null) {
            return [];
        }

        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.tenant_id', $tenant->id)
            ->whereDate('sales.sold_at', today())
            ->when($branchId, fn ($query) => $query->where('sales.branch_id', $branchId))
            ->select(
                'products.name as name',
                DB::raw('SUM(CAST(sale_items.quantity AS DECIMAL(15,4))) as total_quantity'),
                DB::raw('SUM(CAST(sale_items.total_amount AS DECIMAL(15,4))) as total_revenue'),
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'total_quantity' => number_format((float) $item->total_quantity, 4, '.', ''),
                'total_revenue' => number_format((float) $item->total_revenue, 4, '.', ''),
            ])
            ->all();
    }

    /**
     * Build low stock alerts for the current tenant and branch.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildLowStock(?Tenant $tenant, ?string $branchId): array
    {
        if ($tenant === null) {
            return [];
        }

        return Inventory::query()
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('inventories.tenant_id', $tenant->id)
            ->when($branchId, fn ($query) => $query->where('inventories.branch_id', $branchId))
            ->whereRaw('inventories.quantity <= products.alert_quantity')
            ->select('products.name as name', 'inventories.quantity', 'products.alert_quantity')
            ->orderBy('inventories.quantity')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => number_format((float) $item->quantity, 4, '.', ''),
                'alert_quantity' => (int) $item->alert_quantity,
            ])
            ->all();
    }
}
