<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductBrand\StoreProductBrandRequest;
use App\Http\Requests\ProductBrand\UpdateProductBrandRequest;
use App\Models\ProductBrand;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductBrandController extends Controller
{
    /**
     * Display a listing of product brands.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $brands = ProductBrand::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('product-brands/index', [
            'brands' => $brands,
        ]);
    }

    /**
     * Show the form for creating a new product brand.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('product-brands/create');
    }

    /**
     * Store a newly created product brand.
     */
    public function store(StoreProductBrandRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $brand = ProductBrand::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
        ]);

        $auditService->created($brand, ['event' => 'product_brand.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product brand created.')]);

        return to_route('product-brands.index');
    }

    /**
     * Show the form for editing the product brand.
     */
    public function edit(Request $request, ProductBrand $productBrand): Response
    {
        return Inertia::render('product-brands/edit', [
            'brand' => $productBrand,
        ]);
    }

    /**
     * Update the product brand.
     */
    public function update(UpdateProductBrandRequest $request, ProductBrand $productBrand, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $productBrand->update([
            'name' => $validated['name'],
        ]);

        $auditService->updated($productBrand, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product brand updated.')]);

        return to_route('product-brands.index');
    }

    /**
     * Remove the product brand.
     */
    public function destroy(Request $request, ProductBrand $productBrand, AuditService $auditService): RedirectResponse
    {
        $auditService->deleted($productBrand);

        $productBrand->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product brand deleted.')]);

        return to_route('product-brands.index');
    }
}
