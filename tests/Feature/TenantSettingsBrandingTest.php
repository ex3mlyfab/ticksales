<?php

use App\Models\Tenant;
use App\Models\TenantBranding;
use App\Models\TenantSetting;

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
        'primary_color' => '#111827',
        'secondary_color' => '#f5f5f4',
        'accent_color' => '#0f172a',
        'sidebar_style' => 'compact',
        'border_radius' => '0.75rem',
    ]);

    expect($settings->tenant_id)->toBe($tenant->id)
        ->and($branding->tenant_id)->toBe($tenant->id)
        ->and($tenant->fresh()->tenantSetting)->toBeInstanceOf(TenantSetting::class)
        ->and($tenant->fresh()->tenantBranding)->toBeInstanceOf(TenantBranding::class);
});
