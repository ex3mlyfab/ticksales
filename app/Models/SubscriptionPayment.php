<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $plan_id
 * @property string|null $payment_method_id
 * @property int|null $recorded_by
 * @property string $payment_method_name
 * @property string|null $gateway
 * @property string $amount
 * @property string $currency
 * @property string|null $reference
 * @property string|null $gateway_transaction_id
 * @property Carbon $paid_at
 * @property Carbon $period_starts_at
 * @property Carbon $period_ends_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SubscriptionPayment extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'payment_method_id',
        'recorded_by',
        'payment_method_name',
        'gateway',
        'amount',
        'currency',
        'reference',
        'gateway_transaction_id',
        'paid_at',
        'period_starts_at',
        'period_ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<SubscriptionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * @return BelongsTo<SubscriptionPaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPaymentMethod::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
