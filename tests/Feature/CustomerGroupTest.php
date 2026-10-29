<?php

use App\Models\CustomerGroup;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access customer groups', function () {
    get('/customer-groups')->assertRedirect('/login');
});

test('authenticated users can view customer groups', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/customer-groups')
        ->assertStatus(200);
});

test('authenticated users can create a customer group', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $response = actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/customer-groups', [
            'tenant_id' => $tenant->id,
            'name' => 'Wholesale',
            'discount_rate' => 15.00,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('customer-groups.index'));

    $group = CustomerGroup::query()->where('name', 'Wholesale')->first();

    expect($group)->not->toBeNull();
    expect($group->name)->toBe('Wholesale');
    expect($group->discount_rate)->toBe(15.0);
    expect($group->tenant_id)->toBe($tenant->id);
});

test('authenticated users can update a customer group', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $group = CustomerGroup::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Group',
    ]);

    $groupId = $group->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/customer-groups/'.$groupId, [
            'tenant_id' => $tenant->id,
            'name' => 'New Group',
            'discount_rate' => 20.00,
        ])
        ->assertRedirect(route('customer-groups.index'));

    $group->refresh();

    expect($group->name)->toBe('New Group');
    expect($group->discount_rate)->toBe(20.0);
});

test('authenticated users can delete a customer group', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $group = CustomerGroup::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $groupId = $group->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/customer-groups/'.$groupId)
        ->assertRedirect(route('customer-groups.index'));

    $foundGroup = CustomerGroup::query()->withTrashed()->find($groupId);

    expect($foundGroup)->not->toBeNull();
    expect($foundGroup->trashed())->toBeTrue();
});

test('user cannot delete customer group with associated customers', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $group = CustomerGroup::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    // Create a customer in this group
    Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_group_id' => $group->id,
    ]);

    $groupId = $group->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/customer-groups/'.$groupId)
        ->assertRedirect(route('customer-groups.index'));

    $group->refresh();

    expect($group->trashed())->toBeFalse();
});
