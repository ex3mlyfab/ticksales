<?php

namespace App\Policies;

use App\Models\ProductUnit;
use App\Models\User;

class ProductUnitPolicy
{
    /**
     * Determine whether the user can view any units.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can view the unit.
     */
    public function view(User $user, ProductUnit $productUnit): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can create units.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.create');
    }

    /**
     * Determine whether the user can update the unit.
     */
    public function update(User $user, ProductUnit $productUnit): bool
    {
        return $user->hasPermissionTo('products.update');
    }

    /**
     * Determine whether the user can delete the unit.
     */
    public function delete(User $user, ProductUnit $productUnit): bool
    {
        return $user->hasPermissionTo('products.delete');
    }
}
