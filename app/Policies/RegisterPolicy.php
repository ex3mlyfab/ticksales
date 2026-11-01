<?php

namespace App\Policies;

use App\Models\Register;
use App\Models\User;

class RegisterPolicy
{
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function view(User $user, Register $register): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function update(User $user, Register $register): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function delete(User $user, Register $register): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }
}
