<?php

namespace App\Support\Financials;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes profit and loss figures for a tenant (optionally scoped to one
 * branch/store) by aggregating the recorded transaction tables — no
 * accounting ledger is required.
 *
 * Revenue comes from sales and is reduced by refunds issued during the
 * period (sale returns). Cost of goods sold uses the cost_price snapshot
 * stored per sale item. Operating expenses accrue on expense_date and are
 * broken down by expense category. Note: sale_return_items carry no cost
 * snapshot, so returned stock's cost is not subtracted from COGS.
 */
class BranchProfitAndLoss
{
    /**
     * @param  string|null  $branchId  Null aggregates across all branches.
     * @return array{revenue: string, returns_amount: string, net_revenue: string, cogs: string, gross_profit: string, expenses_by_category: array<int, array{name: string, amount: string}>, operating_expenses: string, net_profit: string, sales_count: int}
     */
    public static function forPeriod(?Tenant $tenant, ?string $branchId, Carbon $from, Carbon $to): array
    {
        if ($tenant === null) {
            return self::emptyResult();
        }

        $tenantId = $tenant->id;

        $salesQuery = Sale::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('sold_at', [$from, $to])
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId));

        $revenue = (float) (clone $salesQuery)
            ->sum(DB::raw('CAST(total_amount AS DECIMAL(15,4))'));

        $salesCount = (int) (clone $salesQuery)->count();

        $returnsAmount = (float) SaleReturn::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('returned_at', [$from, $to])
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->sum(DB::raw('CAST(refund_amount AS DECIMAL(15,4))'));

        $cogs = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.tenant_id', $tenantId)
            ->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId !== null, fn (Builder $q) => $q->where('sales.branch_id', $branchId))
            ->sum(DB::raw('CAST(sale_items.quantity AS DECIMAL(15,4))'
                .'*CAST(sale_items.cost_price AS DECIMAL(15,4))'));

        $expensesByCategory = Expense::query()
            ->where('expenses.tenant_id', $tenantId)
            ->whereBetween('expenses.expense_date', [$from, $to])
            ->when($branchId !== null, fn (Builder $q) => $q->where('expenses.branch_id', $branchId))
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->select(
                'expense_categories.name as name',
                DB::raw('SUM(CAST(expenses.amount AS DECIMAL(15,4))) as amount'),
            )
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('amount')
            ->get();

        $operatingExpenses = (float) Expense::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->sum(DB::raw('CAST(amount AS DECIMAL(15,4))'));

        $netRevenue = $revenue - $returnsAmount;
        $grossProfit = $netRevenue - $cogs;
        $netProfit = $grossProfit - $operatingExpenses;

        return [
            'revenue' => self::format($revenue),
            'returns_amount' => self::format($returnsAmount),
            'net_revenue' => self::format($netRevenue),
            'cogs' => self::format($cogs),
            'gross_profit' => self::format($grossProfit),
            'expenses_by_category' => $expensesByCategory->map(fn ($row) => [
                'name' => $row->name,
                'amount' => self::format((float) $row->amount),
            ])->all(),
            'operating_expenses' => self::format($operatingExpenses),
            'net_profit' => self::format($netProfit),
            'sales_count' => $salesCount,
        ];
    }

    private static function emptyResult(): array
    {
        return [
            'revenue' => '0.0000',
            'returns_amount' => '0.0000',
            'net_revenue' => '0.0000',
            'cogs' => '0.0000',
            'gross_profit' => '0.0000',
            'expenses_by_category' => [],
            'operating_expenses' => '0.0000',
            'net_profit' => '0.0000',
            'sales_count' => 0,
        ];
    }

    private static function format(float $amount): string
    {
        return number_format($amount, 4, '.', '');
    }
}
