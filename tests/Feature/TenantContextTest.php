<?php

use App\Http\Middleware\SetTenantContext;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Route;

test('it resolves the active tenant from the current session for authenticated users', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role_id' => null,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    session(['active_tenant_id' => $tenant->id]);

    Route::middleware(['web', 'auth', SetTenantContext::class])
        ->get('/tenant-context-test', function () {
            return response()->json([
                'tenant_id' => app(TenantContext::class)->tenant?->id,
            ]);
        });

    $this->actingAs($user)
        ->get('/tenant-context-test')
        ->assertOk()
        ->assertJsonPath('tenant_id', $tenant->id);
});
