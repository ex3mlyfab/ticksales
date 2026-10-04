<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display the inventory snapshots for the active tenant.
     */
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $inventory = Inventory::query()
            ->with(['product', 'branch'])
            ->where('tenant_id', $tenant?->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Inventory $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'branch_id' => $item->branch_id,
                'branch_name' => $item->branch?->name,
                'quantity' => $item->quantity,
                'variant_id' => $item->variant_id,
            ]);

        return response()->json([
            'data' => $inventory,
        ]);
    }
}
