<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    /**
     * Determine whether the user can view inventory.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    /**
     * Determine whether the user can view the inventory record.
     */
    public function view(User $user, Inventory $inventory): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    /**
     * Determine whether the user can adjust inventory.
     */
    public function adjust(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('inventory.adjust');
    }

    /**
     * Determine whether the user can transfer stock.
     */
    public function transfer(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('inventory.transfer');
    }
}
