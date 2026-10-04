<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $categories = ProductCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $category) => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image_path' => $category->image_path,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
            ]);

        return response()->json([
            'data' => $categories,
        ]);
    }
}
