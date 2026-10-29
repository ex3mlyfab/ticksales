<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.create');
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.update');
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.delete');
    }
}
