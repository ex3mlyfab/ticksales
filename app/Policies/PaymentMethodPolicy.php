<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->hasPermissionTo('pos.manage');
    }
}
