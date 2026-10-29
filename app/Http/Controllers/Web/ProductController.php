<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\TaxRate;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $products = Product::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['category', 'brand', 'unit', 'taxRate'])
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('products/index', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $categories = ProductCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $brands = ProductBrand::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = ProductUnit::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('products/create', [
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'taxRates' => $taxRates,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $product = Product::query()->create([
            'tenant_id' => $tenant?->id,
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'],
            'unit_id' => $validated['unit_id'],
            'tax_rate_id' => $validated['tax_rate_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'description' => $validated['description'] ?? null,
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'has_variants' => $validated['has_variants'],
            'track_inventory' => $validated['track_inventory'],
            'alert_quantity' => $validated['alert_quantity'],
            'image_path' => $validated['image_path'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        $auditService->created($product, ['event' => 'product.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('products.index');
    }

    /**
     * Show the form for editing the product.
     */
    public function edit(Request $request, Product $product): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($product->tenant_id === $tenant?->id, 403);

        $categories = ProductCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $brands = ProductBrand::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = ProductUnit::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $taxRates = TaxRate::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('products/edit', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'taxRates' => $taxRates,
        ]);
    }

    /**
     * Update the product.
     */
    public function update(UpdateProductRequest $request, Product $product, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($product->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $product->update([
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'],
            'unit_id' => $validated['unit_id'],
            'tax_rate_id' => $validated['tax_rate_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'description' => $validated['description'] ?? null,
            'cost_price' => $validated['cost_price'],
            'selling_price' => $validated['selling_price'],
            'has_variants' => $validated['has_variants'],
            'track_inventory' => $validated['track_inventory'],
            'alert_quantity' => $validated['alert_quantity'],
            'image_path' => $validated['image_path'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        $auditService->updated($product, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('products.index');
    }

    /**
     * Remove the product.
     */
    public function destroy(Request $request, Product $product, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($product->tenant_id === $tenant?->id, 403);

        $auditService->deleted($product);

        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('products.index');
    }
}
