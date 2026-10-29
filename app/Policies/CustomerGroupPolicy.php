<?php

namespace App\Policies;

use App\Models\CustomerGroup;
use App\Models\User;

class CustomerGroupPolicy
{
    /**
     * Determine whether the user can view any customer groups.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    /**
     * Determine whether the user can view the customer group.
     */
    public function view(User $user, CustomerGroup $customerGroup): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    /**
     * Determine whether the user can create customer groups.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('customers.create');
    }

    /**
     * Determine whether the user can update the customer group.
     */
    public function update(User $user, CustomerGroup $customerGroup): bool
    {
        return $user->hasPermissionTo('customers.update');
    }

    /**
     * Determine whether the user can delete the customer group.
     */
    public function delete(User $user, CustomerGroup $customerGroup): bool
    {
        return $user->hasPermissionTo('customers.delete');
    }
}
