<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

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
            'reference_no' => 'PO-'.fake()->unique()->numerify('####'),
            'status' => 'draft',
            'order_date' => fake()->date(),
            'expected_date' => fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d'),
            'subtotal' => fake()->randomFloat(4, 10, 5000),
            'tax_amount' => fake()->randomFloat(4, 0, 500),
            'discount_amount' => fake()->randomFloat(4, 0, 200),
            'total' => fake()->randomFloat(4, 10, 5500),
            'notes' => fake()->sentence(),
        ];
    }
}
