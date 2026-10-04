<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $country
 * @property string $currency
 * @property string $timezone
 * @property string $locale
 * @property bool $is_active
 * @property Carbon|null $trial_ends_at
 * @property string|null $plan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'country',
        'currency',
        'timezone',
        'locale',
        'is_active',
        'trial_ends_at',
        'plan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the users that belong to this tenant.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->select('users.*')
            ->withPivot(['role_id', 'is_owner', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the tenant membership records for the tenant.
     */
    public function tenantUsers()
    {
        return $this->hasMany(TenantUser::class);
    }
}
