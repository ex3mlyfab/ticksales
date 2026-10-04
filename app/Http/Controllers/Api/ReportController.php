<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display a sales summary report for the current tenant and branch.
     */
    public function salesSummary(Request $request)
    {
        $tenant = $request->attributes->get('tenant');
        $branchId = $request->query('branch_id', $request->session()->get('active_branch_id'));

        $from = Carbon::parse($request->query('from', today()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', today()->toDateString()))->endOfDay();

        $salesQuery = Sale::query()
            ->where('tenant_id', $tenant?->id)
            ->whereBetween('sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        $sales = $salesQuery->get();

        $itemsSold = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.tenant_id', $tenant?->id)
            ->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('sales.branch_id', $branchId))
            ->sum(DB::raw('CAST(sale_items.quantity AS DECIMAL(15,4))'));

        $totalSales = (float) $sales->sum('total_amount');
        $ordersCount = $sales->count();

        return response()->json([
            'data' => [
                'total_sales' => number_format($totalSales, 4, '.', ''),
                'orders_count' => $ordersCount,
                'items_sold' => number_format((float) $itemsSold, 4, '.', ''),
                'average_ticket' => number_format($ordersCount > 0 ? ($totalSales / $ordersCount) : 0, 4, '.', ''),
            ],
        ]);
    }

    /**
     * Display the register session summary for a given session.
     */
    public function registerSession(Request $request, string $session)
    {
        $tenant = $request->attributes->get('tenant');

        $registerSession = RegisterSession::query()
            ->with(['sales', 'cashMovements'])
            ->where('tenant_id', $tenant?->id)
            ->findOrFail($session);

        $salesTotal = (float) $registerSession->sales->sum('total_amount');
        $cashIn = (float) $registerSession->cashMovements()->where('type', 'cash_in')->sum('amount');
        $cashOut = (float) $registerSession->cashMovements()->where('type', 'cash_out')->sum('amount');

        return response()->json([
            'data' => [
                'id' => $registerSession->id,
                'register_id' => $registerSession->register_id,
                'tenant_id' => $registerSession->tenant_id,
                'branch_id' => $registerSession->branch_id,
                'status' => $registerSession->status,
                'opening_balance' => $registerSession->opening_balance,
                'closing_balance' => $registerSession->closing_balance,
                'actual_balance' => $registerSession->actual_balance,
                'variance' => $registerSession->variance,
                'sales_total' => number_format($salesTotal, 4, '.', ''),
                'cash_in' => number_format($cashIn, 4, '.', ''),
                'cash_out' => number_format($cashOut, 4, '.', ''),
                'net_cash' => number_format($cashIn - $cashOut, 4, '.', ''),
                'opened_at' => $registerSession->opened_at?->toIso8601String(),
                'closed_at' => $registerSession->closed_at?->toIso8601String(),
            ],
        ]);
    }
}
