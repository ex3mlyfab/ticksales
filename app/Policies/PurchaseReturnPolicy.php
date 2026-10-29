<?php

namespace App\Policies;

use App\Models\PurchaseReturn;
use App\Models\User;

class PurchaseReturnPolicy
{
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('purchases.view');
    }

    public function view(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->hasPermissionTo('purchases.view');
    }

    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('purchases.create');
    }

    public function update(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->hasPermissionTo('purchases.update');
    }

    public function delete(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->hasPermissionTo('purchases.delete');
    }

    public function post(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->hasPermissionTo('purchases.create');
    }
}
