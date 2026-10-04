<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => fake()->randomFloat(4, 0, 250),
        ];
    }
}
