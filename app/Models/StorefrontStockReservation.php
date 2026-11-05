<?php

namespace App\Models;

use Database\Factories\StorefrontStockReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $storefront_order_item_id
 * @property string $inventory_id
 * @property string $quantity
 * @property string $status
 */
class StorefrontStockReservation extends Model
{
    /** @use HasFactory<StorefrontStockReservationFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'storefront_order_item_id',
        'inventory_id',
        'quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(StorefrontOrderItem::class, 'storefront_order_item_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
