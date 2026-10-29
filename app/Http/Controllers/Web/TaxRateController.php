<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxRate\StoreTaxRateRequest;
use App\Http\Requests\TaxRate\UpdateTaxRateRequest;
use App\Models\TaxRate;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxRateController extends Controller
{
    /**
     * Display a listing of tax rates.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $taxRates = TaxRate::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('tax-rates/index', [
            'taxRates' => $taxRates,
        ]);
    }

    /**
     * Show the form for creating a new tax rate.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('tax-rates/create');
    }

    /**
     * Store a newly created tax rate.
     */
    public function store(StoreTaxRateRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $taxRate = TaxRate::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
            'rate' => $validated['rate'],
            'is_inclusive' => $validated['is_inclusive'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $auditService->created($taxRate, ['event' => 'tax_rate.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax rate created.')]);

        return to_route('tax-rates.index');
    }

    /**
     * Show the form for editing the tax rate.
     */
    public function edit(Request $request, TaxRate $taxRate): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($taxRate->tenant_id === $tenant?->id, 403);

        return Inertia::render('tax-rates/edit', [
            'taxRate' => $taxRate,
        ]);
    }

    /**
     * Update the tax rate.
     */
    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($taxRate->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $taxRate->update([
            'name' => $validated['name'],
            'rate' => $validated['rate'],
            'is_inclusive' => $validated['is_inclusive'] ?? false,
            'is_active' => $validated['is_active'],
        ]);

        $auditService->updated($taxRate, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax rate updated.')]);

        return to_route('tax-rates.index');
    }

    /**
     * Remove the tax rate.
     */
    public function destroy(Request $request, TaxRate $taxRate, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($taxRate->tenant_id === $tenant?->id, 403);

        $auditService->deleted($taxRate);

        $taxRate->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax rate deleted.')]);

        return to_route('tax-rates.index');
    }
}
