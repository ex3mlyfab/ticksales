<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseReturn>
 */
class PurchaseReturnFactory extends Factory
{
    protected $model = PurchaseReturn::class;

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
            'supplier_id' => Supplier::factory(),
            'purchase_id' => Purchase::factory(),
            'reference_no' => 'PR-'.fake()->unique()->numerify('####'),
            'reason' => fake()->sentence(),
            'return_date' => fake()->date(),
            'subtotal' => fake()->randomFloat(4, 10, 2000),
            'tax_amount' => fake()->randomFloat(4, 0, 200),
            'discount_amount' => fake()->randomFloat(4, 0, 50),
            'total' => fake()->randomFloat(4, 10, 2200),
            'status' => 'pending',
            'notes' => fake()->sentence(),
        ];
    }
}
