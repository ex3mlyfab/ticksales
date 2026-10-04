<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

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
            'register_session_id' => RegisterSession::factory(),
            'customer_id' => Customer::factory(),
            'reference_no' => 'SALE-'.fake()->unique()->numerify('#####'),
            'idempotency_key' => fake()->uuid(),
            'status' => 'completed',
            'subtotal' => fake()->randomFloat(4, 10, 500),
            'discount_amount' => 0,
            'tax_amount' => fake()->randomFloat(4, 0, 25),
            'total_amount' => fake()->randomFloat(4, 10, 500),
            'paid_amount' => fake()->randomFloat(4, 10, 500),
            'change_amount' => 0,
            'outstanding_amount' => 0,
            'note' => fake()->sentence(),
            'sold_by' => User::factory(),
            'sold_at' => now(),
            'synced_from_offline' => false,
            'offline_created_at' => null,
        ];
    }
}
