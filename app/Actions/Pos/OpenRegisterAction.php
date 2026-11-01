<?php

namespace App\Actions\Pos;

use App\Models\Register;
use App\Models\RegisterSession;
use Illuminate\Support\Facades\DB;

class OpenRegisterAction
{
    /**
     * Open a register session for a given register and cashier.
     */
    public static function execute(array $data): RegisterSession
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];
            $registerId = $data['register_id'];
            $branchId = $data['branch_id'];
            $openedBy = $data['opened_by'];
            $openingBalance = $data['opening_balance'] ?? 0;

            $register = Register::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($registerId);

            // Ensure no session is already open for this register
            $openSession = RegisterSession::query()
                ->where('register_id', $registerId)
                ->where('status', 'open')
                ->first();

            if ($openSession) {
                throw new \RuntimeException('A session is already open for this register.');
            }

            return RegisterSession::query()->create([
                'register_id' => $registerId,
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'opened_by' => $openedBy,
                'opening_balance' => $openingBalance,
                'status' => 'open',
                'opened_at' => now(),
            ]);
        });
    }
}
