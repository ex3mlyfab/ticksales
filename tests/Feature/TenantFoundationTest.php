<?php

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

test('tenant records can be created and retrieved', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Acme Retail',
        'slug' => 'acme-retail',
    ]);

    $storedTenant = Tenant::query()->findOrFail($tenant->id);

    expect($storedTenant->id)->toBeString()
        ->not->toBeEmpty()
        ->and($storedTenant->name)->toBe('Acme Retail')
        ->and($storedTenant->slug)->toBe('acme-retail');
});

test('a user can belong to multiple tenants', function () {
    $user = User::factory()->create();
    $firstTenant = Tenant::factory()->create();
    $secondTenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $firstTenant->id,
        'user_id' => $user->getKey(),
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    TenantUser::query()->create([
        'tenant_id' => $secondTenant->id,
        'user_id' => $user->getKey(),
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    expect($user->tenants()->count())->toBe(2)
        ->and($user->tenants()->pluck('id')->sort()->values()->all())
        ->toEqualCanonicalizing([$firstTenant->id, $secondTenant->id]);
});
