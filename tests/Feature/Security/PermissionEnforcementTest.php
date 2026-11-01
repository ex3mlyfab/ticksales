<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Regression coverage for the route-level `permission:...` middleware and the
 * tenant-scoped permission resolution. The owner role is attached by the
 * global test helper; these tests deliberately create restricted members to
 * exercise both the allow and the deny path.
 */
test('tenant owner can access a permission-gated page', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/products')
        ->assertOk();
});

test('member without the required permission is denied', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    // No role, not an owner: the member has no permissions at all.
    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/products')
        ->assertForbidden();
});

test('member with an unrelated role is denied the route it lacks', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $permission = Permission::firstOrCreate(['name' => 'customers.view']);
    $role = Role::firstOrCreate(['name' => 'sales_cashier'], ['description' => 'Cashier']);
    $role->permissions()->sync([$permission->id]);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    // Has customers.view but not products.view.
    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/products')
        ->assertForbidden();

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/customers')
        ->assertOk();
});

test('permissions granted in one tenant are not leaked into another tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->create();

    // Owner of tenant A (full permissions)...
    attachUserToTenant($user, $tenantA);

    // ...but only a restricted member of tenant B (customers.view only).
    $permission = Permission::firstOrCreate(['name' => 'customers.view']);
    $role = Role::firstOrCreate(['name' => 'b_member'], ['description' => 'Tenant B member']);
    $role->permissions()->sync([$permission->id]);

    TenantUser::query()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    // Operating in tenant B, the owner-level permissions of tenant A must not
    // apply: products.view is not granted in B, so the page is forbidden.
    actingAs($user)
        ->withSession(['active_tenant_id' => $tenantB->id])
        ->get('/products')
        ->assertForbidden();

    // The permission that IS granted in tenant B still works.
    actingAs($user)
        ->withSession(['active_tenant_id' => $tenantB->id])
        ->get('/customers')
        ->assertOk();
});

test('api denies a member without the required permission', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/products')
        ->assertForbidden();
});

test('api allows a tenant owner', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/products')
        ->assertOk();
});
