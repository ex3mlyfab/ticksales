<?php

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('tenant users can list customers through the api', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    Customer::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Alicia Johnson',
        'email' => 'alicia@example.com',
        'phone' => '555-1001',
        'credit_limit' => '500.0000',
    ]);

    Customer::factory()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'name' => 'Other Tenant Customer',
        'email' => 'other@example.com',
        'credit_limit' => '999.0000',
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/customers');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alicia Johnson')
        ->assertJsonPath('data.0.email', 'alicia@example.com');
});

test('tenant users can create a customer through the api', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->postJson('/api/v1/customers', [
            'name' => 'Jamila Smith',
            'email' => 'jamila@example.com',
            'phone' => '555-2002',
            'address' => '12 Market Street',
            'credit_limit' => '750.0000',
            'is_active' => true,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Jamila Smith')
        ->assertJsonPath('data.tenant_id', $tenant->id);

    $this->assertDatabaseHas('customers', [
        'tenant_id' => $tenant->id,
        'name' => 'Jamila Smith',
        'email' => 'jamila@example.com',
    ]);
});
