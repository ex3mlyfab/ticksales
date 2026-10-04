<?php

namespace App\Concerns;

use App\Models\Role;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

trait HasPermissions
{
    /**
     * Get the roles assigned to the user across their tenant memberships.
     *
     * @return HasManyThrough
     */
    public function roles()
    {
        return $this->hasManyThrough(Role::class, TenantUser::class, 'user_id', 'id', 'id', 'role_id');
    }

    /**
     * Get the permissions granted to the user through their active roles.
     */
    public function permissions(): Collection
    {
        $roleIds = $this->tenantUsers()->whereNotNull('role_id')->pluck('role_id');

        return Role::query()
            ->with('permissions')
            ->whereIn('id', $roleIds)
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id');
    }

    /**
     * Determine if the user has a given permission.
     */
    public function hasPermissionTo(string $permission): bool
    {
        $permission = strtolower(trim($permission));

        return $this->permissions()->contains(fn ($value) => strtolower((string) $value->name) === $permission);
    }

    /**
     * Determine if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Assign a role to a tenant membership for this user.
     */
    public function assignRoleToTenant(string $tenantId, string $roleId): void
    {
        $this->tenantUsers()->updateOrCreate(
            ['tenant_id' => $tenantId],
            ['role_id' => $roleId, 'is_owner' => false, 'is_active' => true],
        );
    }
}
