<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustmentItem>
 */
class StockAdjustmentItemFactory extends Factory
{
    protected $model = StockAdjustmentItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'adjustment_id' => StockAdjustment::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity_before' => fake()->randomFloat(4, 0, 100),
            'quantity_after' => fake()->randomFloat(4, 0, 100),
            'quantity_adjusted' => fake()->randomFloat(4, -20, 20),
            'unit_cost' => fake()->randomFloat(4, 0, 10),
        ];
    }
}
