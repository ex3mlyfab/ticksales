<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegisterSession>
 */
class RegisterSessionFactory extends Factory
{
    protected $model = RegisterSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'register_id' => Register::factory(),
            'tenant_id' => Tenant::factory(),
            'branch_id' => Branch::factory(),
            'opened_by' => User::factory(),
            'opening_balance' => '0.0000',
            'status' => 'open',
            'opened_at' => now(),
        ];
    }
}
