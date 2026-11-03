<?php

namespace App\Support\Financials;

use App\Models\CashMovement;
use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes a simplified balance sheet snapshot for a tenant (optionally
 * scoped to one branch/store) as of a date, derived from existing tables —
 * no accounting ledger is required.
 *
 * Cash on hand combines the closing balances of closed register sessions
 * with the net recorded cash movements of sessions still open as of the
 * date. Inventory value is quantity x product cost price (no
 * moving-average cost is tracked). Receivables sum sales'
 * outstanding_amount and payables sum purchases' total minus paid amounts;
 * the denormalized customer/supplier outstanding_balance columns are
 * tenant-level and deliberately not used because they cannot be attributed
 * to a branch.
 *
 * Equity is implied rather than ledgered: owner_capital balances the sheet
 * (assets - liabilities - retained earnings), since cash injected into
 * registers at opening time has no dedicated capital account.
 */
class BranchBalanceSheet
{
    /**
     * @param  string|null  $branchId  Null aggregates across all branches.
     * @return array{cash_on_hand: string, accounts_receivable: string, inventory_value: string, total_assets: string, accounts_payable: string, total_liabilities: string, retained_earnings: string, owner_capital: string, total_equity: string}
     */
    public static function asOf(?Tenant $tenant, ?string $branchId, Carbon $asOf): array
    {
        if ($tenant === null) {
            return self::emptyResult();
        }

        $tenantId = $tenant->id;
        $branchScope = fn (Builder $query) => self::branchScope($query, $branchId);

        $cashOnHand = self::cashOnHand($tenantId, $branchId, $asOf);

        $accountsReceivable = (float) $branchScope(
            Sale::query()->where('tenant_id', $tenantId),
        )
            ->where('sold_at', '<=', $asOf)
            ->sum(DB::raw('CAST(outstanding_amount AS DECIMAL(15,4))'));

        $inventoryValue = (float) Inventory::query()
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->where('inventories.tenant_id', $tenantId)
            ->when($branchId !== null, fn (Builder $q) => $q->where('inventories.branch_id', $branchId))
            ->sum(DB::raw('CAST(inventories.quantity AS DECIMAL(15,4))'
                .'*CAST(products.cost_price AS DECIMAL(15,4))'));

        $accountsPayable = (float) $branchScope(
            Purchase::query()->where('tenant_id', $tenantId),
        )
            ->whereDate('purchase_date', '<=', $asOf)
            ->sum(DB::raw('CAST(total AS DECIMAL(15,4)) - CAST(paid_amount AS DECIMAL(15,4))'));

        $totalAssets = $cashOnHand + $accountsReceivable + $inventoryValue;
        $totalLiabilities = $accountsPayable;
        $totalEquity = $totalAssets - $totalLiabilities;

        $retainedEarnings = (float) self::cumulativeNetProfit($tenant, $branchId, $asOf);
        $ownerCapital = $totalEquity - $retainedEarnings;

        return [
            'cash_on_hand' => self::format($cashOnHand),
            'accounts_receivable' => self::format($accountsReceivable),
            'inventory_value' => self::format($inventoryValue),
            'total_assets' => self::format($totalAssets),
            'accounts_payable' => self::format($accountsPayable),
            'total_liabilities' => self::format($totalLiabilities),
            'retained_earnings' => self::format($retainedEarnings),
            'owner_capital' => self::format($ownerCapital),
            'total_equity' => self::format($totalEquity),
        ];
    }

    private static function emptyResult(): array
    {
        return [
            'cash_on_hand' => '0.0000',
            'accounts_receivable' => '0.0000',
            'inventory_value' => '0.0000',
            'total_assets' => '0.0000',
            'accounts_payable' => '0.0000',
            'total_liabilities' => '0.0000',
            'retained_earnings' => '0.0000',
            'owner_capital' => '0.0000',
            'total_equity' => '0.0000',
        ];
    }

    /**
     * Cash in registers: sessions closed on or before the date contribute
     * their closing balance; sessions that were still open at that moment
     * contribute their opening balance plus net cash movements up to it.
     */
    private static function cashOnHand(string $tenantId, ?string $branchId, Carbon $asOf): float
    {
        $closedCash = (float) self::branchScope(
            RegisterSession::query()->where('tenant_id', $tenantId),
            $branchId,
        )
            ->where('status', 'closed')
            ->where('closed_at', '<=', $asOf)
            ->sum(DB::raw('CAST(closing_balance AS DECIMAL(15,4))'));

        $openSessions = self::branchScope(
            RegisterSession::query()->where('tenant_id', $tenantId),
            $branchId,
        )
            ->where('opened_at', '<=', $asOf)
            ->where(fn (Builder $q) => $q
                ->where('status', 'open')
                ->orWhere(fn (Builder $inner) => $inner
                    ->where('status', 'closed')
                    ->where('closed_at', '>', $asOf)))
            ->get(['id', 'opening_balance']);

        if ($openSessions->isNotEmpty()) {
            $sessionIds = $openSessions->pluck('id')->all();

            $netMovements = (float) CashMovement::query()
                ->whereIn('register_session_id', $sessionIds)
                ->where('created_at', '<=', $asOf)
                ->selectRaw('COALESCE(SUM(CASE WHEN type = \'in\' '
                    .'THEN CAST(amount AS DECIMAL(15,4)) '
                    .'ELSE -CAST(amount AS DECIMAL(15,4)) END), 0) as net_movements')
                ->value('net_movements');

            $openCash = (float) $openSessions->sum(
                fn ($session) => (float) $session->opening_balance,
            ) + $netMovements;
        } else {
            $openCash = 0.0;
        }

        return $closedCash + $openCash;
    }

    /**
     * Net profit since the beginning of time up to the snapshot date.
     */
    private static function cumulativeNetProfit(Tenant $tenant, ?string $branchId, Carbon $asOf): string
    {
        $profit = BranchProfitAndLoss::forPeriod(
            $tenant,
            $branchId,
            Carbon::create(2000, 1, 1, 0, 0, 0),
            $asOf,
        );

        return $profit['net_profit'];
    }

    private static function branchScope(Builder $query, ?string $branchId): Builder
    {
        return $query->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId));
    }

    private static function format(float $amount): string
    {
        return number_format($amount, 4, '.', '');
    }
}
