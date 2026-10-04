<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturnItem>
 */
class SaleReturnItemFactory extends Factory
{
    protected $model = SaleReturnItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(4, 1, 4);
        $unitPrice = fake()->randomFloat(4, 5, 100);

        return [
            'sale_return_id' => SaleReturn::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => 0,
            'tax_amount' => fake()->randomFloat(4, 0, 10),
            'line_total' => $quantity * $unitPrice,
        ];
    }
}
