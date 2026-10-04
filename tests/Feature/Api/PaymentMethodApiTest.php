<?php

use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('tenant users can list active payment methods through the api', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    PaymentMethod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Cash',
        'type' => 'cash',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    PaymentMethod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Card',
        'type' => 'card',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    PaymentMethod::query()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'name' => 'Other Tenant Method',
        'type' => 'cash',
        'is_active' => true,
        'sort_order' => 99,
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/payment-methods');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Cash')
        ->assertJsonPath('data.0.type', 'cash');
});
