<?php

namespace Database\Factories;

use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorefrontOrderItem>
 */
class StorefrontOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'storefront_order_id' => StorefrontOrder::factory(),
            'product_id' => null,
            'variant_id' => null,
            'name' => fake()->words(2, true),
            'sku' => null,
            'quantity' => 1,
            'unit_price' => 1,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'line_subtotal' => 1,
            'line_total' => 1,
            'tax_inclusive' => false,
        ];
    }
}
