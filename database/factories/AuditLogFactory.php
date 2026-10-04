<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

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
            'user_id' => User::factory(),
            'event' => 'sale.created',
            'auditable_type' => 'sale',
            'auditable_id' => fake()->numerify('########'),
            'old_values' => ['amount' => 0],
            'new_values' => ['amount' => fake()->randomFloat(2, 1, 500)],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
