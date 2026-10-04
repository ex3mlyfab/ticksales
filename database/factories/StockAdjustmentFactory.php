<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\StockAdjustment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

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
            'reference_no' => 'ADJ-'.fake()->unique()->numerify('######'),
            'reason' => fake()->sentence(),
            'status' => 'draft',
            'adjusted_by' => null,
            'adjusted_at' => null,
        ];
    }
}
