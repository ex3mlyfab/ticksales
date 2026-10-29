<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBrand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $brands = ProductBrand::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get()
            ->map(fn (ProductBrand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
            ]);

        return response()->json([
            'data' => $brands,
        ]);
    }
}
