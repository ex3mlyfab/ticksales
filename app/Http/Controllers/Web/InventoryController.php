<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    /**
     * Display a listing of inventory stock levels.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $inventory = Inventory::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['product', 'branch', 'variant'])
            ->orderBy('product_id')
            ->paginate(50);

        return Inertia::render('inventory/index', [
            'inventory' => $inventory,
        ]);
    }

    /**
     * Show the stock history for a specific product.
     */
    public function show(Request $request, string $productId): Response
    {
        $tenant = $request->attributes->get('tenant');

        $product = Product::query()
            ->where('tenant_id', $tenant?->id)
            ->findOrFail($productId);

        $inventory = Inventory::query()
            ->where('tenant_id', $tenant?->id)
            ->where('product_id', $productId)
            ->with(['branch', 'variant'])
            ->get();

        return Inertia::render('inventory/show', [
            'product' => $product,
            'inventory' => $inventory,
        ]);
    }
}
