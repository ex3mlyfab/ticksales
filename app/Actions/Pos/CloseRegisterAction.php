<?php

namespace App\Actions\Pos;

use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;

class CloseRegisterAction
{
    /**
     * Close a register session, calculating variance between expected and actual balances.
     */
    public static function execute(array $data): RegisterSession
    {
        return DB::transaction(function () use ($data) {
            $sessionId = $data['register_session_id'];
            $closedBy = $data['closed_by'];
            $actualBalance = $data['actual_balance'] ?? 0;
            $notes = $data['notes'] ?? null;

            $session = RegisterSession::query()->findOrFail($sessionId);

            if ($session->status !== 'open') {
                throw new \RuntimeException('This register session is not open.');
            }

            // Expected drawer balance only includes cash actually received.
            // Card / mobile-money payments never enter the cash drawer, so
            // they are excluded; change handed back reduces the drawer.
            $cashInFromSales = SalePayment::query()
                ->whereHas('sale', fn ($q) => $q->where('register_session_id', $session->id))
                ->whereHas('paymentMethod', fn ($q) => $q->where('type', 'cash'))
                ->sum('amount');

            $cashChangeGiven = Sale::query()
                ->where('register_session_id', $session->id)
                ->whereHas('payments.paymentMethod', fn ($q) => $q->where('type', 'cash'))
                ->sum('change_amount');

            $cashIn = $session->cashMovements()->where('type', 'in')->sum('amount');
            $cashOut = $session->cashMovements()->where('type', 'out')->sum('amount');

            $expectedBalance = (float) $session->opening_balance
                + (float) $cashInFromSales
                - (float) $cashChangeGiven
                + (float) $cashIn
                - (float) $cashOut;

            $variance = (float) $actualBalance - $expectedBalance;

            $session->update([
                'closed_by' => $closedBy,
                'actual_balance' => $actualBalance,
                'expected_balance' => $expectedBalance,
                'variance' => $variance,
                'closing_balance' => $actualBalance,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $notes,
            ]);

            return $session;
        });
    }
}
