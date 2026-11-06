<?php

namespace App\Http\Controllers\Web;

use App\Actions\Storefront\StorefrontCart;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Support\StorefrontAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontCartController extends Controller
{
    public function show(Request $request, Tenant $tenant, StorefrontCart $cart): Response
    {
        $this->assertStorefrontEnabled($tenant);

        return Inertia::render('storefront/cart', [
            'store' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'currency' => $tenant->currency,
            ],
            'items' => $cart->present($request, $tenant),
        ]);
    }

    public function add(Request $request, Tenant $tenant, StorefrontCart $cart, StorefrontAnalytics $analytics): RedirectResponse
    {
        $this->assertStorefrontEnabled($tenant);

        $data = $request->validate([
            'product_id' => ['required', 'ulid'],
            'variant_id' => ['nullable', 'ulid'],
            'quantity' => ['required', 'numeric', 'gt:0', 'lte:9999'],
        ]);

        $product = Product::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($data['product_id'])
            ->where('is_active', true)
            ->where('is_storefront_published', true)
            ->firstOrFail();
        abort_unless($product->isStorefrontListable(), 404);
        $variant = $this->validateVariant($tenant, $product, $data['variant_id'] ?? null);
        $lines = $cart->lines($request, $tenant);
        $key = $product->id.':'.($variant?->id ?? '');
        $quantity = (float) ($lines[$key]['quantity'] ?? 0) + (float) $data['quantity'];

        if ($quantity > 9999) {
            throw ValidationException::withMessages([
                'quantity' => 'The total quantity in your cart cannot exceed 9,999.',
            ]);
        }

        if (! array_key_exists($key, $lines) && count($lines) >= 20) {
            throw ValidationException::withMessages([
                'product_id' => 'Your cart can contain up to 20 different items.',
            ]);
        }

        $lines[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
        ];
        $request->session()->put($cart->sessionKey($tenant), $lines);
        $analytics->record(
            request: $request,
            tenant: $tenant,
            event: 'cart_item_added',
            productId: $product->id,
            variantId: $variant?->id,
        );

        return back()->with('success', 'Product added to your cart.');
    }

    public function update(Request $request, Tenant $tenant, string $product, StorefrontCart $cart): RedirectResponse
    {
        $this->assertStorefrontEnabled($tenant);

        $data = $request->validate([
            'variant_id' => ['nullable', 'ulid'],
            'quantity' => ['required', 'numeric', 'gt:0', 'lte:9999'],
        ]);
        $lines = $cart->lines($request, $tenant);
        $key = $product.':'.($data['variant_id'] ?? '');

        if (! array_key_exists($key, $lines)) {
            abort(404);
        }

        $productModel = Product::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($product)
            ->where('is_active', true)
            ->where('is_storefront_published', true)
            ->firstOrFail();
        abort_unless($productModel->isStorefrontListable(), 404);
        $variant = $this->validateVariant($tenant, $productModel, $data['variant_id'] ?? null);

        $lines[$key] = [
            'product_id' => $productModel->id,
            'variant_id' => $variant?->id,
            'quantity' => (float) $data['quantity'],
        ];
        $request->session()->put($cart->sessionKey($tenant), $lines);

        return back();
    }

    public function destroy(Request $request, Tenant $tenant, string $product, StorefrontCart $cart, StorefrontAnalytics $analytics): RedirectResponse
    {
        $this->assertStorefrontEnabled($tenant);
        $data = $request->validate([
            'variant_id' => ['nullable', 'ulid'],
        ]);
        $lines = $cart->lines($request, $tenant);
        unset($lines[$product.':'.($data['variant_id'] ?? '')]);
        $request->session()->put($cart->sessionKey($tenant), $lines);
        $analytics->record(
            request: $request,
            tenant: $tenant,
            event: 'cart_item_removed',
            productId: $product,
            variantId: $data['variant_id'] ?? null,
        );

        return back();
    }

    private function assertStorefrontEnabled(Tenant $tenant): void
    {
        abort_unless(
            $tenant->is_active && ($tenant->tenantSetting?->is_storefront_enabled ?? true),
            404,
        );
    }

    private function validateVariant(Tenant $tenant, Product $product, ?string $variantId): ?ProductVariant
    {
        if (! $product->has_variants && $variantId === null) {
            return null;
        }

        if ($variantId === null) {
            throw ValidationException::withMessages([
                'variant_id' => 'Choose an available product option.',
            ]);
        }

        if (! $product->has_variants) {
            throw ValidationException::withMessages([
                'variant_id' => 'This product does not have selectable options.',
            ]);
        }

        $variant = ProductVariant::query()
            ->where('tenant_id', $tenant->id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereKey($variantId)
            ->firstOrFail();

        abort_unless(
            filled($variant->name)
                && $variant->selling_price !== null
                && (float) $variant->selling_price > 0,
            404,
        );

        return $variant;
    }
}
