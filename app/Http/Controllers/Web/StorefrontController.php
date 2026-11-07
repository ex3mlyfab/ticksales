<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\TenantBranding;
use App\Support\StorefrontAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function directory(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'size:26'],
            'fulfillment' => ['nullable', 'in:all,pickup,delivery'],
        ]);

        $q = filled($validated['q'] ?? null) ? trim((string) $validated['q']) : null;
        $categoryId = $validated['category'] ?? null;
        $fulfillment = $validated['fulfillment'] ?? 'all';

        $tenants = Tenant::query()
            ->where('is_active', true)
            ->whereHas('tenantSetting', fn ($settings) => $settings->where('is_storefront_enabled', true))
            ->whereHas('products', fn ($products) => $products->storefrontListable())
            ->with([
                'tenantSetting',
                'tenantBranding',
                'products' => fn ($products) => $products
                    ->storefrontListable()
                    ->select(['id', 'tenant_id', 'name', 'selling_price', 'image_path'])
                    ->orderBy('name')
                    ->limit(3),
            ])
            ->withCount(['products as storefront_products_count' => fn ($products) => $products->storefrontListable()])
            ->when($q, function ($query, $q) {
                $query->where(function ($query) use ($q) {
                    $query->where('name', 'like', '%'.$q.'%')
                        ->orWhereHas('tenantSetting', fn ($settings) => $settings->where('storefront_description', 'like', '%'.$q.'%'));
                });
            })
            ->when($fulfillment === 'pickup', fn ($query) => $query->whereHas(
                'tenantSetting',
                fn ($settings) => $settings->where('storefront_pickup_enabled', true),
            ))
            ->when($fulfillment === 'delivery', fn ($query) => $query->whereHas(
                'tenantSetting',
                fn ($settings) => $settings->where('storefront_delivery_enabled', true),
            ))
            ->when($categoryId, fn ($query) => $query->whereHas(
                'products',
                fn ($products) => $products
                    ->storefrontListable()
                    ->where('category_id', $categoryId),
            ))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Tenant $tenant): array => $this->directoryStoreSummary($tenant));

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->whereHas('tenant', fn ($tenants) => $tenants
                ->where('is_active', true)
                ->whereHas('tenantSetting', fn ($settings) => $settings->where('is_storefront_enabled', true)))
            ->whereHas('tenant.products', fn ($products) => $products->storefrontListable())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ProductCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values()
            ->all();

        return Inertia::render('storefront/directory', [
            'stores' => $tenants,
            'categories' => $categories,
            'filters' => [
                'q' => $q,
                'category' => $categoryId,
                'fulfillment' => $fulfillment,
            ],
        ]);
    }

    public function marketplace(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'size:26'],
            'brand' => ['nullable', 'string', 'size:26'],
            'store' => ['nullable', 'string', 'size:26'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'sort' => ['nullable', 'in:newest,name,price_low,price_high'],
        ]);

        $q = filled($validated['q'] ?? null) ? trim((string) $validated['q']) : null;
        $categoryId = $validated['category'] ?? null;
        $brandId = $validated['brand'] ?? null;
        $storeId = $validated['store'] ?? null;
        $minPrice = $validated['min_price'] ?? null;
        $maxPrice = $validated['max_price'] ?? null;
        $sort = $validated['sort'] ?? 'newest';

        $tenantIds = Tenant::query()
            ->where('is_active', true)
            ->whereHas('tenantSetting', fn ($settings) => $settings->where('is_storefront_enabled', true))
            ->pluck('id');

        $products = Product::query()
            ->whereIn('tenant_id', $tenantIds)
            ->storefrontListable()
            ->with(['tenant:id,name,slug,currency', 'tenant.tenantBranding:id,tenant_id,logo_path', 'category:id,name', 'brand:id,name'])
            ->when($q, fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%');
            }))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->when($storeId, fn ($query) => $query->where('tenant_id', $storeId))
            ->when($minPrice !== null, fn ($query) => $query->where(function ($query) use ($minPrice) {
                $query->where(function ($query) use ($minPrice) {
                    $query->where('has_variants', false)
                        ->whereRaw('CAST(selling_price AS DECIMAL(15,4)) >= ?', [$minPrice]);
                })->orWhere(function ($query) use ($minPrice) {
                    $query->where('has_variants', true)
                        ->whereHas('variants', fn ($variants) => $variants
                            ->where('is_active', true)
                            ->whereRaw('CAST(selling_price AS DECIMAL(15,4)) >= ?', [$minPrice]));
                });
            }))
            ->when($maxPrice !== null, fn ($query) => $query->where(function ($query) use ($maxPrice) {
                $query->where(function ($query) use ($maxPrice) {
                    $query->where('has_variants', false)
                        ->whereRaw('CAST(selling_price AS DECIMAL(15,4)) <= ?', [$maxPrice]);
                })->orWhere(function ($query) use ($maxPrice) {
                    $query->where('has_variants', true)
                        ->whereHas('variants', fn ($variants) => $variants
                            ->where('is_active', true)
                            ->whereRaw('CAST(selling_price AS DECIMAL(15,4)) <= ?', [$maxPrice]));
                });
            }))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name')->orderBy('id'))
            ->when($sort === 'price_low', fn ($query) => $query->orderByRaw('CAST(selling_price AS DECIMAL(15,4)) ASC')->orderBy('name'))
            ->when($sort === 'price_high', fn ($query) => $query->orderByRaw('CAST(selling_price AS DECIMAL(15,4)) DESC')->orderBy('name'))
            ->when($sort === 'newest', fn ($query) => $query->latest()->orderBy('id'))
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                ...$this->productSummary($product),
                'category' => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
                'brand' => $product->brand ? ['id' => $product->brand->id, 'name' => $product->brand->name] : null,
                'store' => [
                    'name' => $product->tenant->name,
                    'slug' => $product->tenant->slug,
                    'currency' => $product->tenant->currency,
                    'logo_url' => $this->publicImageUrl($product->tenant->tenantBranding?->logo_path),
                ],
            ]);

        $categories = ProductCategory::query()
            ->whereIn('tenant_id', $tenantIds)
            ->where('is_active', true)
            ->whereHas('tenant.products', fn ($products) => $products->storefrontListable())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ProductCategory $category): array => ['id' => $category->id, 'name' => $category->name])
            ->values()
            ->all();

        $brands = ProductBrand::query()
            ->whereIn('tenant_id', $tenantIds)
            ->whereIn('id', Product::query()
                ->whereIn('tenant_id', $tenantIds)
                ->storefrontListable()
                ->whereNotNull('brand_id')
                ->distinct()
                ->pluck('brand_id'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ProductBrand $brand): array => ['id' => $brand->id, 'name' => $brand->name])
            ->values()
            ->all();

        $stores = Tenant::query()
            ->whereIn('id', $tenantIds)
            ->whereHas('products', fn ($products) => $products->storefrontListable())
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Tenant $tenant): array => ['id' => $tenant->id, 'name' => $tenant->name, 'slug' => $tenant->slug])
            ->values()
            ->all();

        return Inertia::render('storefront/marketplace', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'stores' => $stores,
            'filters' => [
                'q' => $q,
                'category' => $categoryId,
                'brand' => $brandId,
                'store' => $storeId,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        abort_unless(
            $tenant->is_active && ($tenant->tenantSetting?->is_storefront_enabled ?? true),
            404,
        );

        $products = $tenant->products()
            ->storefrontListable()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->through(fn (Product $product): array => $this->productSummary($product));

        return Inertia::render('storefront/show', [
            'store' => $this->storeDetails($tenant),
            'products' => $products,
        ]);
    }

    public function product(Request $request, Tenant $tenant, Product $product, StorefrontAnalytics $analytics): Response
    {
        abort_unless(
            $tenant->is_active
                && ($tenant->tenantSetting?->is_storefront_enabled ?? true)
                && $product->tenant_id === $tenant->id
                && $product->is_active
                && $product->is_storefront_published
                && $product->load('variants')->isStorefrontListable(),
            404,
        );

        $analytics->record(
            request: $request,
            tenant: $tenant,
            event: 'product_viewed',
            productId: $product->id,
        );

        $variants = ProductVariant::query()
            ->where('tenant_id', $tenant->id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'selling_price', 'image_path']);

        return Inertia::render('storefront/product', [
            'store' => $this->storeDetails($tenant),
            'product' => [
                ...$this->productSummary($product),
                'variants' => $variants
                    ->map(fn (ProductVariant $variant): array => [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'selling_price' => $variant->selling_price ?? $product->selling_price,
                        'image_url' => $this->publicImageUrl($variant->image_path),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * @return array{name: string, slug: string, currency: string, logo_url: string|null, description: string|null, email: string|null, phone: string|null, policy: string|null, pickup_enabled: bool, delivery_enabled: bool}
     */
    private function directoryStoreSummary(Tenant $tenant): array
    {
        $settings = $tenant->tenantSetting;

        return [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'currency' => $tenant->currency,
            'logo_url' => $this->publicImageUrl($tenant->tenantBranding?->logo_path),
            'description' => $settings?->storefront_description,
            'pickup_enabled' => $settings?->storefront_pickup_enabled ?? true,
            'delivery_enabled' => $settings?->storefront_delivery_enabled ?? true,
            'product_count' => (int) $tenant->storefront_products_count,
            'featured_products' => $tenant->products
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'selling_price' => $product->selling_price,
                    'image_url' => $this->publicImageUrl($product->image_path),
                ])
                ->values()
                ->all(),
        ];
    }

    private function storeDetails(Tenant $tenant): array
    {
        $branding = TenantBranding::query()
            ->where('tenant_id', $tenant->id)
            ->first(['logo_path']);
        $settings = $tenant->tenantSetting;

        return [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'currency' => $tenant->currency,
            'logo_url' => $this->publicImageUrl($branding?->logo_path),
            'description' => $settings?->storefront_description,
            'email' => $settings?->storefront_email,
            'phone' => $settings?->storefront_phone,
            'policy' => $settings?->storefront_policy,
            'pickup_enabled' => $settings?->storefront_pickup_enabled ?? true,
            'delivery_enabled' => $settings?->storefront_delivery_enabled ?? true,
        ];
    }

    /**
     * @return array{id: string, name: string, description: string|null, selling_price: string, image_url: string|null, has_variants: bool}
     */
    private function productSummary(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'selling_price' => $product->selling_price,
            'image_url' => $this->publicImageUrl($product->image_path),
            'has_variants' => $product->has_variants,
        ];
    }

    private function publicImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
