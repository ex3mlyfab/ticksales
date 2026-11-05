<?php

namespace App\Models;

use Database\Factories\StorefrontOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $branch_id
 * @property int|null $customer_user_id
 * @property string $reference_no
 * @property string $status
 * @property string $payment_status
 * @property string $payment_method
 * @property string $fulfillment_method
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $delivery_fee
 * @property string $total_amount
 * @property string $tracking_token_hash
 * @property Carbon|null $personal_data_erased_at
 * @property string|null $payment_provider
 * @property string|null $provider_reference
 * @property string|null $provider_event_id
 * @property string|null $carrier_name
 * @property string|null $tracking_number
 */
class StorefrontOrder extends Model
{
    /** @use HasFactory<StorefrontOrderFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'customer_user_id',
        'reference_no',
        'idempotency_key',
        'status',
        'payment_status',
        'payment_method',
        'fulfillment_method',
        'subtotal',
        'tax_amount',
        'delivery_fee',
        'total_amount',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'payment_reference',
        'cancellation_note',
        'refund_handled_externally',
        'refund_note',
        'tracking_token_hash',
        'personal_data_erased_at',
        'payment_provider',
        'provider_reference',
        'provider_event_id',
        'carrier_name',
        'tracking_number',
    ];

    protected $hidden = [
        'tracking_token_hash',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'delivery_fee' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'payment_confirmed_at' => 'datetime',
            'refund_handled_externally' => 'boolean',
            'personal_data_erased_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StorefrontOrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(StorefrontOrderEvent::class);
    }
}
