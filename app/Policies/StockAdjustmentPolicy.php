<?php

namespace App\Policies;

use App\Models\StockAdjustment;
use App\Models\User;

class StockAdjustmentPolicy
{
    /**
     * Determine whether the user can view any stock adjustments.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    /**
     * Determine whether the user can view the stock adjustment.
     */
    public function view(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    /**
     * Determine whether the user can create stock adjustments.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('inventory.adjust');
    }

    /**
     * Determine whether the user can update the stock adjustment.
     */
    public function update(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->hasPermissionTo('inventory.adjust');
    }

    /**
     * Determine whether the user can delete the stock adjustment.
     */
    public function delete(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->hasPermissionTo('inventory.adjust');
    }

    /**
     * Determine whether the user can post (finalize) the stock adjustment.
     */
    public function post(User $user, StockAdjustment $stockAdjustment): bool
    {
        return $user->hasPermissionTo('inventory.adjust');
    }
}
