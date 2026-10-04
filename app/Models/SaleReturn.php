<?php

namespace App\Models;

use Database\Factories\SaleReturnFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $sale_id
 * @property string $tenant_id
 * @property string $branch_id
 * @property string $reference_no
 * @property string|null $reason
 * @property string $refund_amount
 * @property string|null $refund_method_id
 * @property string $status
 * @property int $returned_by
 * @property Carbon $returned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SaleReturn extends Model
{
    /** @use HasFactory<SaleReturnFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_id',
        'tenant_id',
        'branch_id',
        'reference_no',
        'reason',
        'refund_amount',
        'refund_method_id',
        'status',
        'returned_by',
        'returned_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'refund_amount' => 'decimal:4',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * Get the sale being returned.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the tenant that owns the return.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the branch that handled the return.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the payment method used for the refund.
     */
    public function refundMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'refund_method_id');
    }

    /**
     * Get the user who processed the return.
     */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * Get the return line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
