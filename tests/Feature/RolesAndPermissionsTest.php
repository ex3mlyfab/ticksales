<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('a user can check a permission granted through their role', function () {
    $permission = Permission::query()->create([
        'name' => 'products.create',
        'description' => 'Create products',
    ]);

    $role = Role::query()->create([
        'name' => 'manager',
        'description' => 'Manager role',
    ]);

    $role->permissions()->attach($permission);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->getKey(),
        'role_id' => $role->id,
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    expect($user->can('products.create'))->toBeTrue()
        ->and($user->hasPermissionTo('products.create'))->toBeTrue();
});

test('a user without the permission cannot access it', function () {
    $user = User::factory()->create();

    expect($user->can('products.create'))->toBeFalse()
        ->and($user->hasPermissionTo('products.create'))->toBeFalse();
});

test('a platform administrator receives all permissions without a tenant membership', function () {
    $productsPermission = Permission::query()->create([
        'name' => 'products.view',
        'description' => 'View products',
    ]);
    $reportsPermission = Permission::query()->create([
        'name' => 'reports.view',
        'description' => 'View reports',
    ]);
    $user = User::factory()->create(['is_platform_admin' => true]);

    expect($user->permissions()->modelKeys())
        ->toEqual([$productsPermission->id, $reportsPermission->id]);
});
