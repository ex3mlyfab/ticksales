<?php

namespace App\Models;

use Database\Factories\RegisterSessionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $register_id
 * @property string $tenant_id
 * @property string $branch_id
 * @property int $opened_by
 * @property int|null $closed_by
 * @property string $opening_balance
 * @property string|null $closing_balance
 * @property string|null $expected_balance
 * @property string|null $actual_balance
 * @property string|null $variance
 * @property string|null $notes
 * @property string $status
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RegisterSession extends Model
{
    /** @use HasFactory<RegisterSessionFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'register_id',
        'tenant_id',
        'branch_id',
        'opened_by',
        'closed_by',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'actual_balance',
        'variance',
        'notes',
        'status',
        'opened_at',
        'closed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:4',
            'closing_balance' => 'decimal:4',
            'expected_balance' => 'decimal:4',
            'actual_balance' => 'decimal:4',
            'variance' => 'decimal:4',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Get the register this session belongs to.
     */
    public function register(): BelongsTo
    {
        return $this->belongsTo(Register::class);
    }

    /**
     * Get the tenant that owns the session.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the branch that owns the session.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who opened the register.
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Get the user who closed the register.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Get the sales for this register session.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
