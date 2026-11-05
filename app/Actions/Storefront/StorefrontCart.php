<?php

namespace App\Actions\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorefrontCart
{
    public function __construct(private StorefrontPricing $pricing) {}

    /**
     * @return array<int|string, array{product_id: string, variant_id: string|null, quantity: float}>
     */
    public function lines(Request $request, Tenant $tenant): array
    {
        $cart = $request->session()->get($this->sessionKey($tenant), []);

        return is_array($cart) ? $cart : [];
    }

    /**
     * @return array<int, array{
     *     product_id: string,
     *     variant_id: string|null,
     *     quantity: float,
     *     name: string,
     *     unit_price: string|null,
     *     line_subtotal: float|null,
     *     tax_amount: float|null,
     *     line_total: float|null,
     *     tax_inclusive: bool|null,
     *     image_url: string|null,
     *     available: bool
     * }>
     */
    public function present(Request $request, Tenant $tenant): array
    {
        $lines = $this->lines($request, $tenant);
        $productIds = collect($lines)
            ->pluck('product_id')
            ->filter(fn (mixed $id): bool => is_string($id))
            ->unique()
            ->values();
        $variantIds = collect($lines)
            ->pluck('variant_id')
            ->filter(fn (mixed $id): bool => is_string($id))
            ->unique()
            ->values();

        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->where('is_storefront_published', true)
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $variantIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');
        $settings = TenantSetting::query()->where('tenant_id', $tenant->id)->first();

        return collect($lines)
            ->map(function (array $line) use ($products, $variants, $tenant, $settings): array {
                $product = $products->get($line['product_id'] ?? '');
                $variant = isset($line['variant_id'])
                    ? $variants->get($line['variant_id'])
                    : null;
                $available = $product !== null
                    && $product->isStorefrontListable()
                    && ($product->has_variants
                        ? $variant !== null
                            && $variant->product_id === $product->id
                            && filled($variant->name)
                            && $variant->selling_price !== null
                            && (float) $variant->selling_price > 0
                        : $variant === null);
                $unitPrice = $available
                    ? ($variant?->selling_price ?? $product->selling_price)
                    : null;
                $quantity = is_numeric($line['quantity'] ?? null)
                    ? (float) $line['quantity']
                    : 0.0;
                $linePricing = $available
                    ? $this->pricing->calculate($tenant, $settings, $product, $variant, $quantity)
                    : null;

                return [
                    'product_id' => (string) ($line['product_id'] ?? ''),
                    'variant_id' => is_string($line['variant_id'] ?? null) ? $line['variant_id'] : null,
                    'quantity' => $quantity,
                    'name' => $available
                        ? ($variant === null ? $product->name : $product->name.' - '.$variant->name)
                        : 'Product no longer available',
                    'unit_price' => $unitPrice,
                    'line_subtotal' => $linePricing['line_subtotal'] ?? null,
                    'tax_amount' => $linePricing['tax_amount'] ?? null,
                    'line_total' => $linePricing['line_total'] ?? null,
                    'tax_inclusive' => $linePricing['tax_inclusive'] ?? null,
                    'image_url' => $available
                        ? $this->publicImageUrl($variant?->image_path ?? $product->image_path)
                        : null,
                    'available' => $available,
                ];
            })
            ->values()
            ->all();
    }

    public function sessionKey(Tenant $tenant): string
    {
        return 'storefront_cart_'.$tenant->id;
    }

    private function publicImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
