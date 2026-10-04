<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantBranding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantBranding>
 */
class TenantBrandingFactory extends Factory
{
    protected $model = TenantBranding::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => '#111827',
            'secondary_color' => '#f5f5f4',
            'accent_color' => '#0f172a',
            'sidebar_style' => 'default',
            'border_radius' => '0.625rem',
            'theme_preset' => 'neutral',
        ];
    }
}
