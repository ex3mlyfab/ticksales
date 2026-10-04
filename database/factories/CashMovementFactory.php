<?php

namespace Database\Factories;

use App\Models\CashMovement;
use App\Models\RegisterSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'register_session_id' => RegisterSession::factory(),
            'tenant_id' => Tenant::factory(),
            'type' => fake()->randomElement(['cash_in', 'cash_out']),
            'amount' => fake()->randomFloat(4, 5, 200),
            'reason' => fake()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}
