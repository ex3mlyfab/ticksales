<?php

use App\Models\Discount;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users cannot access discounts', function () {
    get('/discounts')->assertRedirect('/login');
});

test('authenticated users can view discounts index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/discounts')
        ->assertStatus(200);
});

test('authenticated users can create a discount', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $response = actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/discounts', [
            'tenant_id' => $tenant->id,
            'name' => 'Holiday Sale',
            'type' => 'percentage',
            'value' => 15.00,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('discounts.index'));

    $discount = Discount::query()->where('name', 'Holiday Sale')->first();

    expect($discount)->not->toBeNull();
    expect($discount->type)->toBe('percentage');
    expect((float) $discount->value)->toBe(15.0);
    expect($discount->tenant_id)->toBe($tenant->id);
});

test('authenticated users can update a discount', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $discount = Discount::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Discount',
    ]);

    $discountId = $discount->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/discounts/'.$discountId, [
            'tenant_id' => $tenant->id,
            'name' => 'New Discount',
            'type' => 'fixed',
            'value' => 5.00,
            'is_active' => true,
        ])
        ->assertRedirect(route('discounts.index'));

    $discount->refresh();

    expect($discount->name)->toBe('New Discount');
    expect($discount->type)->toBe('fixed');
});

test('authenticated users can delete a discount', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $discount = Discount::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $discountId = $discount->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/discounts/'.$discountId)
        ->assertRedirect(route('discounts.index'));

    $foundDiscount = Discount::query()->withTrashed()->find($discountId);

    expect($foundDiscount)->not->toBeNull();
    expect($foundDiscount->trashed())->toBeTrue();
});

test('users cannot access other tenants discounts', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $otherDiscount = Discount::factory()->create([
        'tenant_id' => $otherTenant->id,
    ]);

    $otherDiscountId = $otherDiscount->id;

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/discounts/'.$otherDiscountId.'/edit')
        ->assertStatus(403);
});
