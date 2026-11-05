<?php

namespace App\Actions\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxRate;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Validation\ValidationException;

class StorefrontPricing
{
    /**
     * @return array{
     *     unit_price: float,
     *     tax_rate: float,
     *     tax_amount: float,
     *     line_subtotal: float,
     *     line_total: float,
     *     tax_inclusive: bool
     * }
     */
    public function calculate(
        Tenant $tenant,
        ?TenantSetting $settings,
        Product $product,
        ?ProductVariant $variant,
        float $quantity,
    ): array {
        $unitPrice = (float) ($variant?->selling_price ?? $product->selling_price);
        $taxRate = $this->resolveTaxRate($tenant, $settings, $product);
        $taxPercentage = (float) ($taxRate?->rate ?? 0);
        $isTaxInclusive = (bool) ($settings?->tax_inclusive || $taxRate?->is_inclusive);
        $lineSubtotal = round($quantity * $unitPrice, 4);
        $taxAmount = $taxPercentage === 0.0
            ? 0.0
            : round(
                $isTaxInclusive
                    ? $lineSubtotal * $taxPercentage / (100 + $taxPercentage)
                    : $lineSubtotal * $taxPercentage / 100,
                4,
            );

        return [
            'unit_price' => $unitPrice,
            'tax_rate' => $taxPercentage,
            'tax_amount' => $taxAmount,
            'line_subtotal' => $lineSubtotal,
            'line_total' => $isTaxInclusive ? $lineSubtotal : $lineSubtotal + $taxAmount,
            'tax_inclusive' => $isTaxInclusive,
        ];
    }

    private function resolveTaxRate(Tenant $tenant, ?TenantSetting $settings, Product $product): ?TaxRate
    {
        $taxRateId = $product->tax_rate_id ?? $settings?->default_tax_rate_id;

        if ($taxRateId === null) {
            return null;
        }

        $taxRate = TaxRate::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($taxRateId)
            ->where('is_active', true)
            ->first();

        if ($taxRate === null && $product->tax_rate_id !== null) {
            throw ValidationException::withMessages([
                'items' => "The tax setting for {$product->name} is unavailable. Please contact the store.",
            ]);
        }

        return $taxRate;
    }
}
