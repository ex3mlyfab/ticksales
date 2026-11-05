<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\TenantUser;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('tenant owner can manage storefront availability', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);
    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Main store',
        'is_main' => true,
    ]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'is_storefront_published' => true,
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get(route('tenant.settings'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/tenant')
            ->where('storefront.is_storefront_enabled', true)
            ->where('storefront.bank_transfer_instructions', null)
            ->where('branches.0.id', $branch->id)
            ->where('branches.0.local_delivery_fee', '0.0000')
            ->where('canUpdate', true)
        );

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.update'), [
            'is_storefront_enabled' => false,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tenant.settings'));

    expect($tenant->fresh()->tenantSetting->is_storefront_enabled)->toBeFalse();

    $this->get(route('storefront.show', $tenant->slug))->assertNotFound();
    $this->get(route('storefront.products.show', [$tenant->slug, $product->id]))->assertNotFound();
});

test('tenant owner can save bank transfer instructions', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.update'), [
            'is_storefront_enabled' => true,
            'bank_transfer_instructions' => 'Transfer to the store account and use your order reference.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tenant.settings'));

    expect($tenant->fresh()->tenantSetting->bank_transfer_instructions)
        ->toBe('Transfer to the store account and use your order reference.');
});

test('tenant owner can configure public storefront identity and fulfillment settings', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.update'), [
            'is_storefront_enabled' => true,
            'storefront_description' => 'Fresh local products and weekly specials.',
            'storefront_email' => 'orders@example.test',
            'storefront_phone' => '555-0100',
            'storefront_policy' => 'Returns are accepted within seven days.',
            'storefront_pickup_enabled' => true,
            'storefront_delivery_enabled' => false,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tenant.settings'));

    $settings = $tenant->fresh()->tenantSetting;
    expect($settings->storefront_description)->toBe('Fresh local products and weekly specials.')
        ->and($settings->storefront_email)->toBe('orders@example.test')
        ->and($settings->storefront_phone)->toBe('555-0100')
        ->and($settings->storefront_policy)->toBe('Returns are accepted within seven days.')
        ->and($settings->storefront_pickup_enabled)->toBeTrue()
        ->and($settings->storefront_delivery_enabled)->toBeFalse();

    $this->get(route('storefront.show', $tenant->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/show')
            ->where('store.description', 'Fresh local products and weekly specials.')
            ->where('store.email', 'orders@example.test')
            ->where('store.phone', '555-0100')
            ->where('store.policy', 'Returns are accepted within seven days.')
            ->where('store.pickup_enabled', true)
            ->where('store.delivery_enabled', false)
        );
});

test('tenant storefront setting changes are recorded in the audit log', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.update'), [
            'is_storefront_enabled' => false,
            'bank_transfer_instructions' => 'Bank: 123456789',
        ])
        ->assertRedirect(route('tenant.settings'));

    $settings = $tenant->fresh()->tenantSetting;

    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'event' => 'storefront.settings.updated',
        'auditable_type' => TenantSetting::class,
        'auditable_id' => $settings->id,
    ]);
});

test('tenant owner can update local delivery fee for a branch in the active tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'is_main' => true,
    ]);
    $otherBranch = Branch::factory()->create([
        'tenant_id' => $otherTenant->id,
        'local_delivery_fee' => 3.5,
    ]);
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.branches.delivery-fee.update', $branch), [
            'local_delivery_fee' => 8.25,
            'tenant_id' => $otherTenant->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tenant.settings'));

    expect($branch->fresh()->local_delivery_fee)->toBe('8.2500')
        ->and($otherBranch->fresh()->local_delivery_fee)->toBe('3.5000');
});

test('tenant owner cannot update another tenant branch fee or set a negative fee', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $otherBranch = Branch::factory()->create([
        'tenant_id' => $otherTenant->id,
        'local_delivery_fee' => 3.5,
    ]);
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.branches.delivery-fee.update', $otherBranch), [
            'local_delivery_fee' => 0,
        ])
        ->assertNotFound();

    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->from(route('tenant.settings'))
        ->put(route('tenant.settings.branches.delivery-fee.update', $branch), [
            'local_delivery_fee' => -1,
        ])
        ->assertSessionHasErrors('local_delivery_fee');

    expect($otherBranch->fresh()->local_delivery_fee)->toBe('3.5000')
        ->and($branch->fresh()->local_delivery_fee)->toBe('0.0000');
});

test('storefront settings only update the active tenant and reject invalid values', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);
    TenantSetting::factory()->create([
        'tenant_id' => $otherTenant->id,
        'is_storefront_enabled' => true,
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->from(route('tenant.settings'))
        ->put(route('tenant.settings.update'), [
            'is_storefront_enabled' => 'yes',
            'tenant_id' => $otherTenant->id,
        ])
        ->assertSessionHasErrors('is_storefront_enabled');

    expect($tenant->fresh()->tenantSetting)->toBeNull()
        ->and($otherTenant->fresh()->tenantSetting->is_storefront_enabled)->toBeTrue();

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.update'), [
            'is_storefront_enabled' => false,
            'tenant_id' => $otherTenant->id,
        ])
        ->assertRedirect(route('tenant.settings'));

    expect($tenant->fresh()->tenantSetting->is_storefront_enabled)->toBeFalse()
        ->and($otherTenant->fresh()->tenantSetting->is_storefront_enabled)->toBeTrue();
});

test('member without settings permission cannot change storefront availability', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();
    TenantUser::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.update'), [
            'is_storefront_enabled' => false,
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put(route('tenant.settings.branches.delivery-fee.update', $branch), [
            'local_delivery_fee' => 10,
        ])
        ->assertForbidden();

    expect($tenant->fresh()->tenantSetting)->toBeNull();
    expect($branch->fresh()->local_delivery_fee)->toBe('0.0000');
});
