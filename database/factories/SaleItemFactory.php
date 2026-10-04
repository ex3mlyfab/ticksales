<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(4, 5, 100);
        $quantity = fake()->randomFloat(4, 1, 5);
        $taxRate = fake()->randomFloat(4, 0, 0.2);
        $subtotal = $quantity * $unitPrice;
        $taxAmount = $subtotal * $taxRate;

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-###'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'cost_price' => $unitPrice * 0.7,
            'discount_amount' => 0,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'total_amount' => $subtotal + $taxAmount,
        ];
    }
}
