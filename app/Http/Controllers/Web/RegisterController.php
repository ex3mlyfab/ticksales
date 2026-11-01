<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Register\StoreRegisterRequest;
use App\Http\Requests\Register\UpdateRegisterRequest;
use App\Http\Resources\RegisterResource;
use App\Models\Branch;
use App\Models\Register;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $registers = Register::query()
            ->where('tenant_id', $tenant?->id)
            ->with('branch')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('registers/index', [
            'registers' => RegisterResource::collection($registers),
        ]);
    }

    public function create(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('registers/create', [
            'branches' => $branches,
        ]);
    }

    public function store(StoreRegisterRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $register = Register::query()->create([
            'tenant_id' => $tenant?->id,
            'branch_id' => $validated['branch_id'],
            'name' => $validated['name'],
            'is_active' => $validated['is_active'],
        ]);

        $auditService->created($register, ['event' => 'register.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Register created.')]);

        return to_route('registers.index');
    }

    public function edit(Request $request, Register $register): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($register->tenant_id === $tenant?->id, 403);

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('registers/edit', [
            'register' => $register,
            'branches' => $branches,
        ]);
    }

    public function update(UpdateRegisterRequest $request, Register $register, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($register->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $register->update([
            'branch_id' => $validated['branch_id'],
            'name' => $validated['name'],
            'is_active' => $validated['is_active'],
        ]);

        $auditService->updated($register, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Register updated.')]);

        return to_route('registers.index');
    }

    public function destroy(Request $request, Register $register, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($register->tenant_id === $tenant?->id, 403);

        if ($register->sessions()->exists()) {
            return to_route('registers.index')
                ->with('error', 'Cannot delete register with session history. Deactivate it instead.');
        }

        $auditService->deleted($register);

        $register->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Register deleted.')]);

        return to_route('registers.index');
    }
}
