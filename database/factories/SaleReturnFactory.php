<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturn>
 */
class SaleReturnFactory extends Factory
{
    protected $model = SaleReturn::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'tenant_id' => Tenant::factory(),
            'branch_id' => Branch::factory(),
            'reference_no' => 'RET-'.fake()->unique()->numerify('#####'),
            'reason' => fake()->sentence(),
            'refund_amount' => fake()->randomFloat(4, 10, 200),
            'refund_method_id' => PaymentMethod::factory(),
            'status' => 'completed',
            'returned_by' => User::factory(),
            'returned_at' => now(),
        ];
    }
}
