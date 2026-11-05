<?php

namespace App\Models;

use Database\Factories\StorefrontOrderEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $storefront_order_id
 * @property string $tenant_id
 * @property int|null $actor_id
 * @property string $event
 * @property array<string, mixed>|null $metadata
 */
class StorefrontOrderEvent extends Model
{
    /** @use HasFactory<StorefrontOrderEventFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'storefront_order_id',
        'tenant_id',
        'actor_id',
        'event',
        'from_status',
        'to_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StorefrontOrder::class, 'storefront_order_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
