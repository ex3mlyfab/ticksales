<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\StockTransfer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'from_branch_id' => Branch::factory(),
            'to_branch_id' => Branch::factory(),
            'reference_no' => 'TRF-'.fake()->unique()->numerify('######'),
            'status' => 'pending',
            'note' => fake()->sentence(),
            'transferred_by' => null,
            'transferred_at' => null,
        ];
    }
}
