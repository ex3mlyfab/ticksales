<?php

namespace App\Models;

use Database\Factories\CashMovementFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $register_session_id
 * @property string $tenant_id
 * @property string $type
 * @property string $amount
 * @property string|null $reason
 * @property int $recorded_by
 * @property Carbon|null $created_at
 */
class CashMovement extends Model
{
    /** @use HasFactory<CashMovementFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'register_session_id',
        'tenant_id',
        'type',
        'amount',
        'reason',
        'recorded_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    /**
     * Get the register session associated with this movement.
     */
    public function registerSession(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }

    /**
     * Get the tenant that owns the movement.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who recorded the movement.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
