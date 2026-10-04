<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\Register;
use App\Models\RegisterSession;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /**
     * Display the registers for the active tenant.
     */
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $registers = Register::query()
            ->where('tenant_id', $tenant?->id)
            ->with('sessions')
            ->orderBy('name')
            ->get()
            ->map(fn (Register $register) => $this->serializeRegister($register));

        return response()->json([
            'data' => $registers,
        ]);
    }

    /**
     * Get the current register session for a register.
     */
    public function session(Request $request, string $register)
    {
        $tenant = $request->attributes->get('tenant');

        $registerModel = Register::query()->where('tenant_id', $tenant?->id)->findOrFail($register);

        $session = RegisterSession::query()
            ->where('tenant_id', $tenant?->id)
            ->where('register_id', $registerModel->id)
            ->latest('opened_at')
            ->firstOrFail();

        return response()->json([
            'data' => $this->serializeSession($session),
        ]);
    }

    /**
     * Open a register session.
     */
    public function open(Request $request, string $register)
    {
        $tenant = $request->attributes->get('tenant');

        $registerModel = Register::query()->where('tenant_id', $tenant?->id)->findOrFail($register);

        $validated = $request->validate([
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $existingOpenSession = RegisterSession::query()
            ->where('tenant_id', $tenant?->id)
            ->where('register_id', $registerModel->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if ($existingOpenSession) {
            return response()->json([
                'message' => 'Register already has an open session.',
                'data' => $this->serializeSession($existingOpenSession),
            ], 409);
        }

        $session = RegisterSession::query()->create([
            'register_id' => $registerModel->id,
            'tenant_id' => $tenant?->id,
            'branch_id' => $registerModel->branch_id,
            'opened_by' => $request->user()?->id,
            'opening_balance' => $validated['opening_balance'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return response()->json([
            'data' => $this->serializeSession($session),
        ]);
    }

    /**
     * Close the current register session.
     */
    public function close(Request $request, string $register)
    {
        $tenant = $request->attributes->get('tenant');

        $registerModel = Register::query()->where('tenant_id', $tenant?->id)->findOrFail($register);

        $validated = $request->validate([
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $session = RegisterSession::query()
            ->where('tenant_id', $tenant?->id)
            ->where('register_id', $registerModel->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->firstOrFail();

        $session->update([
            'closed_by' => $request->user()?->id,
            'closing_balance' => $validated['closing_balance'],
            'actual_balance' => $validated['closing_balance'],
            'variance' => (float) $validated['closing_balance'] - (float) $session->opening_balance,
            'notes' => $validated['notes'] ?? $session->notes,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'data' => $this->serializeSession($session->fresh()),
        ]);
    }

    /**
     * Record a cash movement against the current register session.
     */
    public function cashMovement(Request $request, string $register)
    {
        $tenant = $request->attributes->get('tenant');

        $registerModel = Register::query()->where('tenant_id', $tenant?->id)->findOrFail($register);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $session = RegisterSession::query()
            ->where('tenant_id', $tenant?->id)
            ->where('register_id', $registerModel->id)
            ->latest('opened_at')
            ->firstOrFail();

        $movement = CashMovement::query()->create([
            'register_session_id' => $session->id,
            'tenant_id' => $tenant?->id,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'] ?? null,
            'recorded_by' => $request->user()?->id,
        ]);

        return response()->json([
            'data' => $this->serializeCashMovement($movement),
        ], 201);
    }

    /**
     * Serialize a register for API responses.
     *
     * @return array<string, mixed>
     */
    protected function serializeRegister(Register $register): array
    {
        $latestSession = $register->sessions->sortByDesc('opened_at')->first();

        return [
            'id' => $register->id,
            'tenant_id' => $register->tenant_id,
            'branch_id' => $register->branch_id,
            'name' => $register->name,
            'is_active' => $register->is_active,
            'current_session' => $latestSession ? $this->serializeSession($latestSession) : null,
        ];
    }

    /**
     * Serialize a register session.
     *
     * @return array<string, mixed>
     */
    protected function serializeSession(RegisterSession $session): array
    {
        return [
            'id' => $session->id,
            'register_id' => $session->register_id,
            'tenant_id' => $session->tenant_id,
            'branch_id' => $session->branch_id,
            'opened_by' => $session->opened_by,
            'closed_by' => $session->closed_by,
            'opening_balance' => $session->opening_balance,
            'closing_balance' => $session->closing_balance,
            'expected_balance' => $session->expected_balance,
            'actual_balance' => $session->actual_balance,
            'variance' => $session->variance,
            'notes' => $session->notes,
            'status' => $session->status,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
        ];
    }

    /**
     * Serialize a cash movement.
     *
     * @return array<string, mixed>
     */
    protected function serializeCashMovement(CashMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'register_session_id' => $movement->register_session_id,
            'tenant_id' => $movement->tenant_id,
            'type' => $movement->type,
            'amount' => $movement->amount,
            'reason' => $movement->reason,
            'recorded_by' => $movement->recorded_by,
            'created_at' => $movement->created_at?->toIso8601String(),
        ];
    }
}
