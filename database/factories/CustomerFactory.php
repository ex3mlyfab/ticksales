<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_group_id' => CustomerGroup::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'credit_limit' => fake()->randomFloat(4, 0, 5000),
            'outstanding_balance' => fake()->randomFloat(4, 0, 500),
            'loyalty_points' => fake()->numberBetween(0, 500),
            'is_active' => true,
        ];
    }
}
