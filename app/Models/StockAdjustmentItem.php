<?php

namespace App\Models;

use Database\Factories\StockAdjustmentItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $adjustment_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string $quantity_before
 * @property string $quantity_after
 * @property string $quantity_adjusted
 * @property string|null $unit_cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StockAdjustmentItem extends Model
{
    /** @use HasFactory<StockAdjustmentItemFactory> */
    use HasFactory, HasUlids;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'adjustment_id',
        'product_id',
        'variant_id',
        'quantity_before',
        'quantity_after',
        'quantity_adjusted',
        'unit_cost',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_before' => 'decimal:4',
            'quantity_after' => 'decimal:4',
            'quantity_adjusted' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    /**
     * Get the adjustment that owns the item.
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    /**
     * Get the product for the adjustment item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant for the adjustment item.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
