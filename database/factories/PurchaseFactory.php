<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

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
            'reference_no' => 'PUR-'.fake()->unique()->numerify('####'),
            'invoice_no' => 'INV-'.fake()->unique()->numerify('####'),
            'status' => 'completed',
            'purchase_date' => fake()->date(),
            'subtotal' => fake()->randomFloat(4, 10, 5000),
            'tax_amount' => fake()->randomFloat(4, 0, 500),
            'discount_amount' => fake()->randomFloat(4, 0, 200),
            'total' => fake()->randomFloat(4, 10, 5500),
            'paid_amount' => fake()->randomFloat(4, 10, 5500),
            'notes' => fake()->sentence(),
        ];
    }
}
