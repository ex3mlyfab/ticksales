<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('purchases.view');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->hasPermissionTo('purchases.view');
    }

    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('purchases.create');
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $user->hasPermissionTo('purchases.update');
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->hasPermissionTo('purchases.delete');
    }

    public function post(User $user, Purchase $purchase): bool
    {
        return $user->hasPermissionTo('purchases.create');
    }
}
