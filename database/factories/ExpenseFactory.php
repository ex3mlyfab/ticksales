<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

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
            'expense_category_id' => ExpenseCategory::factory(),
            'reference_no' => 'EXP-'.fake()->unique()->numerify('#####'),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(4, 20, 1000),
            'expense_date' => now()->toDateString(),
            'payment_method_id' => PaymentMethod::factory(),
            'note' => fake()->sentence(),
            'attachment_path' => null,
            'recorded_by' => User::factory(),
        ];
    }
}
