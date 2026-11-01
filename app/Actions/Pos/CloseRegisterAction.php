<?php

namespace App\Actions\Pos;

use App\Models\RegisterSession;
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

            // Calculate expected balance = opening balance + total sales - total cash movements out + movements in
            $totalSales = $session->sales()->sum('total_amount');
            $cashIn = $session->cashMovements()->where('type', 'in')->sum('amount');
            $cashOut = $session->cashMovements()->where('type', 'out')->sum('amount');

            $expectedBalance = (float) $session->opening_balance + (float) $totalSales + (float) $cashIn - (float) $cashOut;
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
