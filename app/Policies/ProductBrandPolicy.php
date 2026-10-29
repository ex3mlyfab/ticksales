<?php

namespace App\Policies;

use App\Models\ProductBrand;
use App\Models\User;

class ProductBrandPolicy
{
    /**
     * Determine whether the user can view any brands.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can view the brand.
     */
    public function view(User $user, ProductBrand $brand): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can create brands.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.create');
    }

    /**
     * Determine whether the user can update the brand.
     */
    public function update(User $user, ProductBrand $brand): bool
    {
        return $user->hasPermissionTo('products.update');
    }

    /**
     * Determine whether the user can delete the brand.
     */
    public function delete(User $user, ProductBrand $brand): bool
    {
        return $user->hasPermissionTo('products.delete');
    }
}
