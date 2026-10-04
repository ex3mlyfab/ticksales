<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display the products available in the active tenant.
     */
    public function index(Request $request)
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
}
