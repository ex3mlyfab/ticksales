<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, ProductCategory $category): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.create');
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, ProductCategory $category): bool
    {
        return $user->hasPermissionTo('products.update');
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, ProductCategory $category): bool
    {
        return $user->hasPermissionTo('products.delete');
    }
}
