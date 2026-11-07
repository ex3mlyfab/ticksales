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
     * Tenant branding stores identity assets (logo, favicon) and an
     * optional permitted accent. By default no accent is set, which
     * renders the platform's own accent.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => null,
            'secondary_color' => null,
            'accent_color' => null,
            'sidebar_style' => 'default',
            'border_radius' => '0.625rem',
            'theme_preset' => 'neutral',
        ];
    }

    /**
     * Configure a permitted tenant accent (e.g. `evergreen`).
     *
     * @param  string  $hex  One of App\Enums\TenantAccent values
     */
    public function accent(string $hex): static
    {
        return $this->state(fn () => ['accent_color' => $hex]);
    }
}
