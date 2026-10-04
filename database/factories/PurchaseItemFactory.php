<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    protected $model = PurchaseItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->randomFloat(4, 1, 20),
            'unit_cost' => fake()->randomFloat(4, 10, 250),
            'tax_rate' => '10.0000',
            'discount_amount' => fake()->randomFloat(4, 0, 10),
            'line_total' => fake()->randomFloat(4, 10, 5000),
            'notes' => fake()->sentence(),
        ];
    }
}
