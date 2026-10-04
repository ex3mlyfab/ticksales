<?php

namespace App\Models;

use Database\Factories\TenantUserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string|null $role_id
 * @property bool $is_owner
 * @property bool $is_active
 * @property Carbon|null $joined_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TenantUser extends Model
{
    /** @use HasFactory<TenantUserFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'role_id',
        'is_owner',
        'is_active',
        'joined_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant for the membership.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user for the membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
