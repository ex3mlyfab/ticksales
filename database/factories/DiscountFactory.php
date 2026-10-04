<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(['percentage', 'fixed']),
            'value' => fake()->randomFloat(4, 5, 50),
            'min_purchase_amount' => null,
            'max_discount_amount' => null,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'is_active' => true,
        ];
    }
}
