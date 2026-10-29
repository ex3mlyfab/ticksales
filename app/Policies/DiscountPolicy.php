<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;

class DiscountPolicy
{
    /**
     * Determine whether the user can view any discounts.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can view the discount.
     */
    public function view(User $user, Discount $discount): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can create discounts.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.create');
    }

    /**
     * Determine whether the user can update the discount.
     */
    public function update(User $user, Discount $discount): bool
    {
        return $user->hasPermissionTo('products.update');
    }

    /**
     * Determine whether the user can delete the discount.
     */
    public function delete(User $user, Discount $discount): bool
    {
        return $user->hasPermissionTo('products.delete');
    }
}
