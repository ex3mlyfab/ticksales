<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    protected $model = InventoryTransaction::class;

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
            'reference_type' => 'Purchase',
            'reference_id' => (string) Str::ulid(),
            'type' => 'Purchase',
            'quantity' => fake()->randomFloat(4, -50, 50),
            'unit_cost' => fake()->randomFloat(4, 0, 100),
            'note' => fake()->sentence(),
            'created_by' => null,
        ];
    }
}
