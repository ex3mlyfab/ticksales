<?php

use App\Models\ProductUnit;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access product units', function () {
    get('/product-units')->assertRedirect('/login');
});

test('authenticated users can view product units index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(route('product-units.index'))
        ->assertOk();
});

test('authenticated users can create a product unit', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $response = actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('product-units.store'), [
            'tenant_id' => $tenant->id,
            'name' => 'Kilogram',
            'abbreviation' => 'kg',
        ]);

    $response->assertRedirect(route('product-units.index'));

    $unit = ProductUnit::query()->where('name', 'Kilogram')->first();

    expect($unit)->not->toBeNull();
    expect($unit->name)->toBe('Kilogram');
    expect($unit->abbreviation)->toBe('kg');
    expect($unit->tenant_id)->toBe($tenant->id);
});

test('authenticated users can update a product unit', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $unit = ProductUnit::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Unit',
    ]);

    $unitId = $unit->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('product-units.update', $unitId), [
            'tenant_id' => $tenant->id,
            'name' => 'New Unit',
            'abbreviation' => 'nu',
        ])
        ->assertRedirect(route('product-units.index'));

    $unit->refresh();

    expect($unit->name)->toBe('New Unit');
    expect($unit->abbreviation)->toBe('nu');
});

test('authenticated users can delete a product unit', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $unit = ProductUnit::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $unitId = $unit->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete(route('product-units.destroy', $unitId))
        ->assertRedirect(route('product-units.index'));

    $foundUnit = ProductUnit::query()->withTrashed()->find($unitId);

    expect($foundUnit)->not->toBeNull();
    expect($foundUnit->trashed())->toBeTrue();
});

test('users cannot access other tenants product units', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherUnit = ProductUnit::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $otherUnitId = $otherUnit->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/product-units/'.$otherUnitId.'/edit')
        ->assertStatus(403);
});
