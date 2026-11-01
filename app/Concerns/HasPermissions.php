<?php

namespace App\Concerns;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

trait HasPermissions
{
    /**
     * Per-tenant cache of resolved permissions, keyed by tenant id. Reset
     * automatically for a new HTTP request because Eloquent models are not
     * shared across requests.
     *
     * @var array<string, Collection<int, Permission>>
     */
    protected array $permissionCache = [];

    /**
     * Per-tenant cache of ownership lookups, keyed by tenant id.
     *
     * @var array<string, bool>
     */
    protected array $ownershipCache = [];

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
     *
     * Permissions are always scoped to the user's active tenant membership so
     * a role in tenant A can never grant permissions while operating in
     * tenant B. When no tenant is supplied the currently active tenant from
     * {@see TenantContext} is used.
     *
     * @return Collection<int, Permission>
     */
    public function permissions(?Tenant $tenant = null): Collection
    {
        $tenant ??= app(TenantContext::class)->getTenant();

        $cacheKey = $tenant?->id ?? 'none';

        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        // Tenant owners hold every permission for their tenant regardless of
        // the roles assigned to the membership.
        if ($this->isOwnerInActiveTenant($tenant)) {
            return $this->permissionCache[$cacheKey] = Permission::query()->get();
        }

        $roleIds = $this->tenantUsers()
            ->where('is_active', true)
            ->whereNotNull('role_id')
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->pluck('role_id');

        $permissions = Role::query()
            ->with('permissions')
            ->whereIn('id', $roleIds)
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id');

        return $this->permissionCache[$cacheKey] = $permissions;
    }

    /**
     * Determine if the user has a given permission.
     *
     * Platform administrators and tenant owners bypass role-based checks
     * entirely, regardless of how many permissions are seeded.
     */
    public function hasPermissionTo(string $permission): bool
    {
        if ($this->is_platform_admin ?? false) {
            return true;
        }

        if ($this->isOwnerInActiveTenant()) {
            return true;
        }

        $permission = strtolower(trim($permission));

        return $this->permissions()->contains(fn ($value) => strtolower((string) $value->name) === $permission);
    }

    /**
     * Determine whether the user owns the given tenant membership.
     *
     * Defaults to the currently active tenant. Without an active tenant there
     * is no ownership context, so no ownership privilege is granted.
     */
    protected function isOwnerInActiveTenant(?Tenant $tenant = null): bool
    {
        $tenant ??= app(TenantContext::class)->getTenant();

        if ($tenant === null) {
            return false;
        }

        $cacheKey = 'owner:'.$tenant->id;

        if (array_key_exists($cacheKey, $this->ownershipCache)) {
            return $this->ownershipCache[$cacheKey];
        }

        return $this->ownershipCache[$cacheKey] = $this->tenantUsers()
            ->where('is_active', true)
            ->where('tenant_id', $tenant->id)
            ->where('is_owner', true)
            ->exists();
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
