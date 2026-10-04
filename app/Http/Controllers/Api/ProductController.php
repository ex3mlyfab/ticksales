<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display the products available in the active tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $products = Product::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'is_active' => $product->is_active,
            ]);

        return response()->json([
            'data' => $products,
        ]);
    }

    /** Display a product with its variants in the active tenant. */
    public function show(Request $request, string $product): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $product = Product::query()
            ->with(['variants' => fn ($variants) => $variants->where('tenant_id', $tenant?->id)])
            ->where('tenant_id', $tenant?->id)
            ->findOrFail($product);

        return response()->json([
            'data' => $this->serializeProduct($product),
        ]);
    }

    /**
     * Search products by name, SKU, or barcode in the active tenant.
     *
     * @throws ValidationException
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'max:255'],
        ]);

        $tenant = $request->attributes->get('tenant');
        $search = $validated['search'];

        $products = Product::query()
            ->with(['variants' => fn ($variants) => $variants->where('tenant_id', $tenant?->id)])
            ->where('tenant_id', $tenant?->id)
            ->where(function ($query) use ($search, $tenant) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('barcodes', fn ($barcodes) => $barcodes
                        ->where('tenant_id', $tenant?->id)
                        ->where('barcode', 'like', "%{$search}%"))
                    ->orWhereHas('variants', function ($variants) use ($search, $tenant) {
                        $variants->where('tenant_id', $tenant?->id)
                            ->where(function ($variants) use ($search, $tenant) {
                                $variants->where('name', 'like', "%{$search}%")
                                    ->orWhere('sku', 'like', "%{$search}%")
                                    ->orWhere('barcode', 'like', "%{$search}%")
                                    ->orWhereHas('barcodes', fn ($barcodes) => $barcodes
                                        ->where('tenant_id', $tenant?->id)
                                        ->where('barcode', 'like', "%{$search}%"));
                            });
                    });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Product $product) => $this->serializeProduct($product));

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProduct(Product $product): array
    {
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'description' => $product->description,
            'cost_price' => $product->cost_price,
            'selling_price' => $product->selling_price,
            'has_variants' => $product->has_variants,
            'track_inventory' => $product->track_inventory,
            'alert_quantity' => $product->alert_quantity,
            'image_path' => $product->image_path,
            'is_active' => $product->is_active,
        ];

        $data['variants'] = $product->variants->map(fn (ProductVariant $variant) => [
            'id' => $variant->id,
            'name' => $variant->name,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'cost_price' => $variant->cost_price,
            'selling_price' => $variant->selling_price,
            'is_active' => $variant->is_active,
        ]);

        return $data;
    }
}
