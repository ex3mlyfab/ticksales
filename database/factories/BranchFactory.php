<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Branch';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'code' => strtoupper(fake()->unique()->lexify('BR?')),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'is_main' => false,
            'is_active' => true,
        ];
    }
}
