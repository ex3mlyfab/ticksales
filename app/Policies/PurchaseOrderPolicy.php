<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('purchases.view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermissionTo('purchases.view');
    }

    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('purchases.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermissionTo('purchases.update');
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermissionTo('purchases.delete');
    }

    public function post(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermissionTo('purchases.create');
    }
}
