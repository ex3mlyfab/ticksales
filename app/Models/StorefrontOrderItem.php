<?php

namespace App\Models;

use Database\Factories\StorefrontOrderItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $storefront_order_id
 * @property string|null $product_id
 * @property string|null $variant_id
 * @property string $name
 * @property string $quantity
 * @property string $unit_price
 * @property string $tax_rate
 * @property string $tax_amount
 * @property string $line_subtotal
 * @property string $line_total
 * @property bool $tax_inclusive
 */
class StorefrontOrderItem extends Model
{
    /** @use HasFactory<StorefrontOrderItemFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'storefront_order_id',
        'product_id',
        'variant_id',
        'name',
        'sku',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'line_subtotal',
        'line_total',
        'tax_inclusive',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_subtotal' => 'decimal:4',
            'line_total' => 'decimal:4',
            'tax_inclusive' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StorefrontOrder::class, 'storefront_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function stockReservation(): HasOne
    {
        return $this->hasOne(StorefrontStockReservation::class);
    }
}
