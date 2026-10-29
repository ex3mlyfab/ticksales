<?php

namespace App\Policies;

use App\Models\TaxRate;
use App\Models\User;

class TaxRatePolicy
{
    /**
     * Determine whether the user can view any tax rates.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can view the tax rate.
     */
    public function view(User $user, TaxRate $taxRate): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    /**
     * Determine whether the user can create tax rates.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('products.create');
    }

    /**
     * Determine whether the user can update the tax rate.
     */
    public function update(User $user, TaxRate $taxRate): bool
    {
        return $user->hasPermissionTo('products.update');
    }

    /**
     * Determine whether the user can delete the tax rate.
     */
    public function delete(User $user, TaxRate $taxRate): bool
    {
        return $user->hasPermissionTo('products.delete');
    }
}
