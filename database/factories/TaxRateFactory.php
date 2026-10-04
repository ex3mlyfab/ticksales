<?php

namespace Database\Factories;

use App\Models\TaxRate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->word().' '.fake()->randomDigitNotZero().'%',
            'rate' => fake()->randomFloat(4, 5, 25),
            'is_inclusive' => false,
            'is_active' => true,
        ];
    }
}
