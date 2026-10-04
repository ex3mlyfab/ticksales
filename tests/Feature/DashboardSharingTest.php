<?php

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard shares tenant, branch, permissions, and branding props', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Downtown Store',
        'code' => 'DTW',
        'is_main' => true,
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    $permission = Permission::query()->create([
        'name' => 'products.view',
        'description' => 'View products',
    ]);

    $role = Role::query()->create([
        'name' => 'manager',
        'description' => 'Manager role',
    ]);

    $role->permissions()->attach($permission);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    session([
        'active_tenant_id' => $tenant->id,
        'active_branch_id' => $branch->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('tenant.id', $tenant->id)
        ->where('tenant.name', $tenant->name)
        ->where('branch.id', $branch->id)
        ->where('branch.name', 'Downtown Store')
        ->where('permissions.0', 'products.view')
        ->where('branding.name', $tenant->name)
    );
});
