<?php

namespace App\Models;

use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $branch_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'product_id',
        'variant_id',
        'quantity',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    /**
     * Get the tenant that owns the inventory snapshot.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the branch for the inventory snapshot.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the product for the inventory snapshot.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant for the inventory snapshot.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
