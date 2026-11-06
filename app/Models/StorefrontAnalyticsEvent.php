<?php

namespace App\Models;

use Database\Factories\StorefrontAnalyticsEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $product_id
 * @property string|null $variant_id
 * @property string $event
 * @property string $session_hash
 */
class StorefrontAnalyticsEvent extends Model
{
    /** @use HasFactory<StorefrontAnalyticsEventFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'event',
        'session_hash',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
