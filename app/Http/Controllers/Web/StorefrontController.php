<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\TenantBranding;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function show(Tenant $tenant): Response
    {
        abort_unless($tenant->is_active, 404);

        $products = $tenant->products()
            ->where('is_active', true)
            ->where('is_storefront_published', true)
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->through(fn (Product $product): array => $this->productSummary($product));

        return Inertia::render('storefront/show', [
            'store' => $this->storeDetails($tenant),
            'products' => $products,
        ]);
    }

    public function product(Tenant $tenant, Product $product): Response
    {
        abort_unless(
            $tenant->is_active
                && $product->tenant_id === $tenant->id
                && $product->is_active
                && $product->is_storefront_published,
            404,
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
     * @return array{name: string, slug: string, currency: string, logo_url: string|null}
     */
    private function storeDetails(Tenant $tenant): array
    {
        $branding = TenantBranding::query()
            ->where('tenant_id', $tenant->id)
            ->first(['logo_path']);

        return [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'currency' => $tenant->currency,
            'logo_url' => $this->publicImageUrl($branding?->logo_path),
        ];
    }

    /**
     * @return array{id: string, name: string, description: string|null, selling_price: string, image_url: string|null}
     */
    private function productSummary(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'selling_price' => $product->selling_price,
            'image_url' => $this->publicImageUrl($product->image_path),
        ];
    }

    private function publicImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
