<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property string $price_monthly
 * @property int $max_branches
 * @property array<int, string>|null $features
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SubscriptionPlan extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'price_monthly',
        'max_branches',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'price_monthly' => 'decimal:2',
        'max_branches' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<Tenant, $this>
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan', 'name');
    }

    /**
     * @return HasMany<SubscriptionPayment, $this>
     */
    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'plan_id');
    }
}
