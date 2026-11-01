<?php

namespace App\Http\Controllers\Web\Pos;

use App\Actions\Pos\CloseRegisterAction;
use App\Actions\Pos\OpenRegisterAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\RegisterSessionResource;
use App\Models\CashMovement;
use App\Models\RegisterSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $sessions = RegisterSession::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['register', 'branch', 'openedBy', 'closedBy'])
            ->orderBy('opened_at', 'desc')
            ->paginate(20);

        return Inertia::render('register-sessions/index', [
            'sessions' => RegisterSessionResource::collection($sessions),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        $validated = $request->validate([
            'register_id' => ['required', 'string', 'exists:registers,id'],
            'branch_id' => ['required', 'string', 'exists:branches,id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $session = OpenRegisterAction::execute([
                'tenant_id' => $tenant?->id,
                'register_id' => $validated['register_id'],
                'branch_id' => $validated['branch_id'],
                'opened_by' => $user->id,
                'opening_balance' => $validated['opening_balance'],
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Register session opened.')]);

            return to_route('pos.index');
        } catch (\RuntimeException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }
    }

    public function close(Request $request, RegisterSession $session): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        abort_unless($session->tenant_id === $tenant?->id, 403);

        $validated = $request->validate([
            'actual_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            CloseRegisterAction::execute([
                'register_session_id' => $session->id,
                'closed_by' => $user->id,
                'actual_balance' => $validated['actual_balance'],
                'notes' => $validated['notes'] ?? null,
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Register session closed.')]);

            return to_route('register-sessions.index');
        } catch (\RuntimeException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }
    }

    public function storeCashMovement(Request $request, RegisterSession $session): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();

        abort_unless($session->tenant_id === $tenant?->id, 403);
        abort_unless($session->status === 'open', 403, 'Register session is not open.');

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:in,out'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
        ]);

        CashMovement::query()->create([
            'register_session_id' => $session->id,
            'tenant_id' => $tenant?->id,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'] ?? null,
            'recorded_by' => $user->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cash movement recorded.')]);

        return back();
    }
}
