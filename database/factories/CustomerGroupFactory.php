<?php

namespace Database\Factories;

use App\Models\CustomerGroup;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerGroup>
 */
class CustomerGroupFactory extends Factory
{
    protected $model = CustomerGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company().' Group',
            'description' => fake()->sentence(),
            'discount_rate' => fake()->randomFloat(4, 0, 25),
            'is_active' => true,
        ];
    }
}
