<?php

namespace App\Models;

use App\Enums\TenantAccent;
use Database\Factories\TenantBrandingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property string|null $primary_color
 * @property string|null $secondary_color
 * @property string|null $accent_color
 * @property string $sidebar_style
 * @property string $border_radius
 * @property string $theme_preset
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TenantBranding extends Model
{
    /** @use HasFactory<TenantBrandingFactory> */
    use HasFactory, HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenant_branding';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'sidebar_style',
        'border_radius',
        'theme_preset',
    ];

    /**
     * The accent colours a tenant is permitted to choose from.
     *
     * Tenant branding is limited to business identity (logo, favicon)
     * and one curated accent. The platform's foundational design tokens
     * — background, primary, sidebar, status colours — stay fixed.
     *
     * @return list<string>
     */
    public static function permittedAccents(): array
    {
        return TenantAccent::permittedHexes();
    }

    /**
     * The tenant's permitted accent, or null when none is set (the
     * platform's default accent applies instead).
     */
    public function permittedAccent(): ?TenantAccent
    {
        return TenantAccent::fromStored($this->accent_color);
    }

    /**
     * Get the tenant that owns the branding.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
