<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\ProductCategory\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of product categories.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $categories = ProductCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('product-categories/index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new product category.
     */
    public function create(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $categories = ProductCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('product-categories/create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created product category.
     */
    public function store(StoreProductCategoryRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $category = ProductCategory::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $auditService->created($category, ['event' => 'product_category.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product category created.')]);

        return to_route('product-categories.index');
    }

    /**
     * Show the form for editing the product category.
     */
    public function edit(Request $request, ProductCategory $productCategory): Response
    {
        $tenant = $request->attributes->get('tenant');

        $categories = ProductCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->whereNull('parent_id')
            ->where('id', '!=', $productCategory->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('product-categories/edit', [
            'category' => $productCategory,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the product category.
     */
    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $productCategory->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $auditService->updated($productCategory, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product category updated.')]);

        return to_route('product-categories.index');
    }

    /**
     * Remove the product category.
     */
    public function destroy(Request $request, ProductCategory $productCategory, AuditService $auditService): RedirectResponse
    {
        $auditService->deleted($productCategory);

        $productCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product category deleted.')]);

        return to_route('product-categories.index');
    }
}
