<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    /**
     * Determine whether the user can view any stock transfers.
     */
    public function viewAny(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    /**
     * Determine whether the user can view the stock transfer.
     */
    public function view(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    /**
     * Determine whether the user can create stock transfers.
     */
    public function create(User $user, ?string $tenantId): bool
    {
        return $user->hasPermissionTo('inventory.transfer');
    }

    /**
     * Determine whether the user can update the stock transfer.
     */
    public function update(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->hasPermissionTo('inventory.transfer');
    }

    /**
     * Determine whether the user can delete the stock transfer.
     */
    public function delete(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->hasPermissionTo('inventory.transfer');
    }

    /**
     * Determine whether the user can post (finalize) the stock transfer.
     */
    public function post(User $user, StockTransfer $stockTransfer): bool
    {
        return $user->hasPermissionTo('inventory.transfer');
    }
}
