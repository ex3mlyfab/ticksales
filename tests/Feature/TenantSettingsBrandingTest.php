<?php

use App\Enums\TenantAccent;
use App\Models\Tenant;
use App\Models\TenantBranding;
use App\Models\TenantSetting;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('creates tenant settings and branding records for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $settings = TenantSetting::query()->create([
        'tenant_id' => $tenant->id,
        'tax_inclusive' => true,
        'receipt_footer' => 'Thank you for shopping with us.',
        'invoice_prefix' => 'INV',
        'stock_alert_threshold' => 12,
        'require_customer_on_sale' => true,
    ]);

    $branding = TenantBranding::query()->create([
        'tenant_id' => $tenant->id,
        'logo_path' => '/storage/logos/sample.png',
        'accent_color' => TenantAccent::Harbor->value,
        'sidebar_style' => 'default',
        'border_radius' => '0.625rem',
    ]);

    expect($settings->tenant_id)->toBe($tenant->id)
        ->and($branding->tenant_id)->toBe($tenant->id)
        ->and($branding->permittedAccent())->toBeInstanceOf(TenantAccent::class)
        ->and($branding->permittedAccent())->toBe(TenantAccent::Harbor)
        ->and($tenant->fresh()->tenantSetting)->toBeInstanceOf(TenantSetting::class)
        ->and($tenant->fresh()->tenantBranding)->toBeInstanceOf(TenantBranding::class);
});

it('resolves unpermitted or absent accents to the platform default', function () {
    $branding = TenantBranding::factory()->create([
        'accent_color' => '#0F172A',
    ]);

    expect($branding->permittedAccent())->toBeNull();
});

it('allows a tenant to save a permitted accent', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/settings/branding', [
            'accent_color' => TenantAccent::Evergreen->value,
        ])
        ->assertRedirect(route('tenant.branding'));

    $branding = $tenant->fresh()->tenantBranding;

    expect($branding)->toBeInstanceOf(TenantBranding::class)
        ->and($branding->accent_color)->toBe(TenantAccent::Evergreen->value);
});

it('rejects accent colours outside the permitted list', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/settings/branding', [
            'accent_color' => '#C6F36A',
        ])
        ->assertSessionHasErrors('accent_color');

    expect($tenant->fresh()->tenantBranding)->toBeNull();
});

it('ignores foundational token fields submitted by tenants', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/settings/branding', [
            'accent_color' => TenantAccent::Harbor->value,
            'primary_color' => '#C6F36A',
            'secondary_color' => '#102B26',
            'sidebar_style' => 'compact',
            'border_radius' => '0',
            'theme_preset' => 'custom',
        ])
        ->assertRedirect(route('tenant.branding'));

    $branding = $tenant->fresh()->tenantBranding;

    expect($branding->accent_color)->toBe(TenantAccent::Harbor->value)
        ->and($branding->primary_color)->toBeNull()
        ->and($branding->secondary_color)->toBeNull()
        ->and($branding->sidebar_style)->toBe('default')
        ->and($branding->border_radius)->toBe('0.625rem')
        ->and($branding->theme_preset)->toBe('neutral');
});
