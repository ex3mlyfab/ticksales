<?php

namespace App\Policies;

use App\Models\SaleReturn;
use App\Models\User;

class SaleReturnPolicy
{
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function view(User $user, SaleReturn $saleReturn): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function update(User $user, SaleReturn $saleReturn): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function delete(User $user, SaleReturn $saleReturn): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }
}
