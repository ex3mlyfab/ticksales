<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductUnit\StoreProductUnitRequest;
use App\Http\Requests\ProductUnit\UpdateProductUnitRequest;
use App\Models\ProductUnit;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductUnitController extends Controller
{
    /**
     * Display a listing of product units.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $units = ProductUnit::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('product-units/index', [
            'units' => $units,
        ]);
    }

    /**
     * Show the form for creating a new product unit.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('product-units/create');
    }

    /**
     * Store a newly created product unit.
     */
    public function store(StoreProductUnitRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $unit = ProductUnit::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'] ?? null,
        ]);

        $auditService->created($unit, ['event' => 'product_unit.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product unit created.')]);

        return to_route('product-units.index');
    }

    /**
     * Show the form for editing the product unit.
     */
    public function edit(Request $request, ProductUnit $productUnit): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($productUnit->tenant_id === $tenant?->id, 403);

        return Inertia::render('product-units/edit', [
            'unit' => $productUnit,
        ]);
    }

    /**
     * Update the product unit.
     */
    public function update(UpdateProductUnitRequest $request, ProductUnit $productUnit, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($productUnit->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $productUnit->update([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'] ?? null,
        ]);

        $auditService->updated($productUnit, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product unit updated.')]);

        return to_route('product-units.index');
    }

    /**
     * Remove the product unit.
     */
    public function destroy(Request $request, ProductUnit $productUnit, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($productUnit->tenant_id === $tenant?->id, 403);

        $auditService->deleted($productUnit);

        $productUnit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product unit deleted.')]);

        return to_route('product-units.index');
    }
}
