<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturnItem>
 */
class PurchaseReturnItemFactory extends Factory
{
    protected $model = PurchaseReturnItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_return_id' => PurchaseReturn::factory(),
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
