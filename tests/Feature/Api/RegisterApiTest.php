<?php

use App\Models\Branch;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('tenant users can list registers and their active session through the api', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $register = Register::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Counter 1',
        'is_active' => true,
    ]);

    Register::query()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'name' => 'Other Tenant Register',
        'is_active' => true,
    ]);

    RegisterSession::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'register_id' => $register->id,
        'opened_by' => $user->id,
        'opening_balance' => '150.0000',
        'status' => 'open',
        'opened_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/registers');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Counter 1');

    $sessionResponse = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/registers/'.$register->id.'/session');

    $sessionResponse->assertOk()
        ->assertJsonPath('data.register_id', $register->id)
        ->assertJsonPath('data.status', 'open');
});

test('tenant users can open and close a register session and record cash movement', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $register = Register::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Counter 2',
        'is_active' => true,
    ]);

    $openResponse = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->postJson('/api/v1/registers/'.$register->id.'/open', [
            'opening_balance' => '120.0000',
            'notes' => 'Opening balance',
        ]);

    $openResponse->assertOk()
        ->assertJsonPath('data.register_id', $register->id)
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.opening_balance', '120.0000');

    $closeResponse = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->postJson('/api/v1/registers/'.$register->id.'/close', [
            'closing_balance' => '142.5000',
            'notes' => 'Closing notes',
        ]);

    $closeResponse->assertOk()
        ->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.closing_balance', '142.5000');

    $cashResponse = $this->actingAs($user)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->postJson('/api/v1/registers/'.$register->id.'/cash-movement', [
            'type' => 'cash_in',
            'amount' => '25.0000',
            'reason' => 'Float added',
        ]);

    $cashResponse->assertCreated()
        ->assertJsonPath('data.type', 'cash_in')
        ->assertJsonPath('data.amount', '25.0000');
});
