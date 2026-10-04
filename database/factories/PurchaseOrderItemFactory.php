<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
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
