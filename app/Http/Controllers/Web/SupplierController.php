<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('suppliers/index', [
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('suppliers/create');
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $supplier = Supplier::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        $auditService->created($supplier, ['event' => 'supplier.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier created.')]);

        return to_route('suppliers.index');
    }

    /**
     * Show the form for editing the supplier.
     */
    public function edit(Request $request, Supplier $supplier): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($supplier->tenant_id === $tenant?->id, 403);

        return Inertia::render('suppliers/edit', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Update the supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($supplier->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $supplier->update([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        $auditService->updated($supplier, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier updated.')]);

        return to_route('suppliers.index');
    }

    /**
     * Remove the supplier.
     */
    public function destroy(Request $request, Supplier $supplier, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($supplier->tenant_id === $tenant?->id, 403);

        $auditService->deleted($supplier);

        $supplier->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier deleted.')]);

        return to_route('suppliers.index');
    }
}
