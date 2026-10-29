<?php

use App\Models\TaxRate;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access tax rates', function () {
    get('/tax-rates')->assertRedirect('/login');
});

test('authenticated users can view tax rates index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(route('tax-rates.index'))
        ->assertOk();
});

test('authenticated users can create a tax rate', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $response = actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('tax-rates.store'), [
            'tenant_id' => $tenant->id,
            'name' => 'VAT 15%',
            'rate' => 15,
            'is_inclusive' => true,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('tax-rates.index'));

    $taxRate = TaxRate::query()->where('name', 'VAT 15%')->first();

    expect($taxRate)->not->toBeNull();
    expect($taxRate->name)->toBe('VAT 15%');
    expect($taxRate->tenant_id)->toBe($tenant->id);
    expect($taxRate->is_inclusive)->toBeTrue();
    expect($taxRate->is_active)->toBeTrue();
});

test('creating a tax rate without flags falls back to sensible defaults', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('tax-rates.store'), [
            'tenant_id' => $tenant->id,
            'name' => 'GST 5%',
            'rate' => 5,
            'is_active' => true,
        ])
        ->assertRedirect(route('tax-rates.index'));

    $taxRate = TaxRate::query()->where('name', 'GST 5%')->first();

    expect($taxRate)->not->toBeNull();
    expect($taxRate->is_inclusive)->toBeFalse();
    expect($taxRate->is_active)->toBeTrue();
});

test('authenticated users can update a tax rate', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $taxRate = TaxRate::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Rate',
        'rate' => 10,
    ]);

    $taxRateId = $taxRate->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tax-rates.update', $taxRateId), [
            'tenant_id' => $tenant->id,
            'name' => 'New Rate',
            'rate' => 14,
            'is_inclusive' => false,
            'is_active' => false,
        ])
        ->assertRedirect(route('tax-rates.index'));

    $taxRate->refresh();

    expect($taxRate->name)->toBe('New Rate');
    expect($taxRate->is_active)->toBeFalse();
});

test('authenticated users can delete a tax rate', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $taxRate = TaxRate::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $taxRateId = $taxRate->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete(route('tax-rates.destroy', $taxRateId))
        ->assertRedirect(route('tax-rates.index'));

    $foundTaxRate = TaxRate::query()->withTrashed()->find($taxRateId);

    expect($foundTaxRate)->not->toBeNull();
    expect($foundTaxRate->trashed())->toBeTrue();
});

test('users cannot access other tenants tax rates', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherTaxRate = TaxRate::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $otherTaxRateId = $otherTaxRate->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/tax-rates/'.$otherTaxRateId.'/edit')
        ->assertStatus(403);
});

test('users cannot update other tenants tax rates', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherTaxRate = TaxRate::factory()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Keep Me',
    ]);

    $otherTaxRateId = $otherTaxRate->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tax-rates.update', $otherTaxRateId), [
            'tenant_id' => $tenant->id,
            'name' => 'Hijacked',
            'rate' => 99,
            'is_active' => true,
        ])
        ->assertStatus(403);

    $otherTaxRate->refresh();

    expect($otherTaxRate->name)->toBe('Keep Me');
});

test('users cannot delete other tenants tax rates', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherTaxRate = TaxRate::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $otherTaxRateId = $otherTaxRate->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete(route('tax-rates.destroy', $otherTaxRateId))
        ->assertStatus(403);

    expect(TaxRate::query()->withTrashed()->find($otherTaxRateId)?->trashed())->toBeFalse();
});
