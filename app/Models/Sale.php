<?php

namespace App\Models;

use Database\Factories\SaleFactory;
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
 * @property string|null $register_session_id
 * @property string|null $customer_id
 * @property string $reference_no
 * @property string $idempotency_key
 * @property string $status
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property string $paid_amount
 * @property string $change_amount
 * @property string $outstanding_amount
 * @property string|null $note
 * @property int $sold_by
 * @property Carbon $sold_at
 * @property bool $synced_from_offline
 * @property Carbon|null $offline_created_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'register_session_id',
        'customer_id',
        'reference_no',
        'idempotency_key',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'outstanding_amount',
        'note',
        'sold_by',
        'sold_at',
        'synced_from_offline',
        'offline_created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'change_amount' => 'decimal:4',
            'outstanding_amount' => 'decimal:4',
            'sold_at' => 'datetime',
            'synced_from_offline' => 'boolean',
            'offline_created_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the sale.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the branch for the sale.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the register session that created the sale.
     */
    public function registerSession(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }

    /**
     * Get the customer associated with the sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who sold the items.
     */
    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    /**
     * Get the sale line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the payments applied to the sale.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }
}
