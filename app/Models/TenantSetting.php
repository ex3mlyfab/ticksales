<?php

namespace App\Models;

use Database\Factories\TenantSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property bool $tax_inclusive
 * @property string|null $default_tax_rate_id
 * @property string|null $receipt_footer
 * @property string $invoice_prefix
 * @property string $receipt_prefix
 * @property string $po_prefix
 * @property int $stock_alert_threshold
 * @property bool $allow_negative_stock
 * @property bool $require_customer_on_sale
 * @property bool $loyalty_enabled
 * @property bool $is_storefront_enabled
 * @property string|null $bank_transfer_instructions
 * @property string|null $storefront_description
 * @property string|null $storefront_email
 * @property string|null $storefront_phone
 * @property string|null $storefront_policy
 * @property bool $storefront_pickup_enabled
 * @property bool $storefront_delivery_enabled
 * @property bool $paystack_enabled
 * @property string|null $paystack_secret
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TenantSetting extends Model
{
    /** @use HasFactory<TenantSettingFactory> */
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'tax_inclusive',
        'default_tax_rate_id',
        'receipt_footer',
        'invoice_prefix',
        'receipt_prefix',
        'po_prefix',
        'stock_alert_threshold',
        'allow_negative_stock',
        'require_customer_on_sale',
        'loyalty_enabled',
        'is_storefront_enabled',
        'bank_transfer_instructions',
        'storefront_description',
        'storefront_email',
        'storefront_phone',
        'storefront_policy',
        'storefront_pickup_enabled',
        'storefront_delivery_enabled',
        'paystack_enabled',
        'paystack_secret',
    ];

    protected $hidden = [
        'paystack_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_inclusive' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'require_customer_on_sale' => 'boolean',
            'loyalty_enabled' => 'boolean',
            'is_storefront_enabled' => 'boolean',
            'storefront_pickup_enabled' => 'boolean',
            'storefront_delivery_enabled' => 'boolean',
            'paystack_enabled' => 'boolean',
            'paystack_secret' => 'encrypted',
            'stock_alert_threshold' => 'integer',
        ];
    }

    /**
     * Get the tenant that owns the settings.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
