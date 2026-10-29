<?php

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access customers', function () {
    get('/customers')->assertRedirect('/login');
});

test('authenticated users can view customers index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(route('customers.index'))
        ->assertOk();
});

test('authenticated users can create a customer', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $group = CustomerGroup::factory()->create(['tenant_id' => $tenant->id]);

    attachUserToTenant($user, $tenant);

    $response = actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/customers', [
            'tenant_id' => $tenant->id,
            'customer_group_id' => $group->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1 555 000 1234',
            'credit_limit' => 500,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('customers.index'));

    $customer = Customer::query()->where('name', 'Jane Doe')->first();

    expect($customer)->not->toBeNull();
    expect($customer->tenant_id)->toBe($tenant->id);
    expect($customer->customer_group_id)->toBe($group->id);
    expect($customer->email)->toBe('jane@example.com');
    expect($customer->is_active)->toBeTrue();
});

test('creating a customer without optionals falls back to sensible defaults', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/customers', [
            'tenant_id' => $tenant->id,
            'name' => 'John Plain',
            'credit_limit' => 0,
            'is_active' => true,
        ])
        ->assertRedirect(route('customers.index'));

    $customer = Customer::query()->where('name', 'John Plain')->first();

    expect($customer)->not->toBeNull();
    expect($customer->customer_group_id)->toBeNull();
    expect($customer->email)->toBeNull();
    expect($customer->loyalty_points)->toBe(0);
});

test('authenticated users can update a customer', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $group = CustomerGroup::factory()->create(['tenant_id' => $tenant->id]);

    attachUserToTenant($user, $tenant);

    $customer = Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Customer',
    ]);

    $customerId = $customer->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/customers/'.$customerId, [
            'tenant_id' => $tenant->id,
            'customer_group_id' => $group->id,
            'name' => 'New Customer',
            'credit_limit' => 1000,
            'loyalty_points' => 120,
            'is_active' => false,
        ])
        ->assertRedirect(route('customers.index'));

    $customer->refresh();

    expect($customer->name)->toBe('New Customer');
    expect($customer->customer_group_id)->toBe($group->id);
    expect($customer->credit_limit)->toBe('1000.0000');
    expect($customer->loyalty_points)->toBe(120);
    expect($customer->is_active)->toBeFalse();
});

test('authenticated users can delete a customer', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $customer = Customer::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $customerId = $customer->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/customers/'.$customerId)
        ->assertRedirect(route('customers.index'));

    $foundCustomer = Customer::query()->withTrashed()->find($customerId);

    expect($foundCustomer)->not->toBeNull();
    expect($foundCustomer->trashed())->toBeTrue();
});

test('users cannot access other tenants customers', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherCustomer = Customer::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $otherCustomerId = $otherCustomer->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/customers/'.$otherCustomerId.'/edit')
        ->assertStatus(403);
});

test('users cannot update other tenants customers', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherCustomer = Customer::factory()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Keep Me',
    ]);

    $otherCustomerId = $otherCustomer->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/customers/'.$otherCustomerId, [
            'tenant_id' => $tenant->id,
            'name' => 'Hijacked',
            'credit_limit' => 999,
            'is_active' => true,
        ])
        ->assertStatus(403);

    $otherCustomer->refresh();

    expect($otherCustomer->name)->toBe('Keep Me');
});

test('users cannot delete other tenants customers', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherCustomer = Customer::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $otherCustomerId = $otherCustomer->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/customers/'.$otherCustomerId)
        ->assertStatus(403);

    expect(Customer::query()->withTrashed()->find($otherCustomerId)?->trashed())->toBeFalse();
});
