<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantSetting>
 */
class TenantSettingFactory extends Factory
{
    protected $model = TenantSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'tax_inclusive' => false,
            'default_tax_rate_id' => null,
            'receipt_footer' => 'Thank you for shopping with us.',
            'invoice_prefix' => 'INV',
            'receipt_prefix' => 'REC',
            'po_prefix' => 'PO',
            'stock_alert_threshold' => 10,
            'allow_negative_stock' => false,
            'require_customer_on_sale' => false,
            'loyalty_enabled' => false,
        ];
    }
}
