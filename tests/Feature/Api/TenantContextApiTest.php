<?php

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('api requests can resolve the active tenant from the authenticated user context', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'name' => 'Northwind POS',
    ]);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonPath('tenant.id', $tenant->id)
        ->assertJsonPath('tenant.name', 'Northwind POS');
});
