<?php

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Tenant;
use App\Models\User;

test('expense categories, expenses, and cash movements can be recorded', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $method = PaymentMethod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Cash',
        'type' => 'cash',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $register = Register::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Counter 1',
        'is_active' => true,
    ]);
    $session = RegisterSession::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'register_id' => $register->id,
        'opened_by' => $cashier->getKey(),
        'opening_balance' => '50.0000',
        'status' => 'open',
        'opened_at' => now(),
    ]);

    $category = ExpenseCategory::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Utilities',
    ]);

    $expense = Expense::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'expense_category_id' => $category->id,
        'reference_no' => 'EXP-1001',
        'description' => 'Electricity bill',
        'amount' => '240.0000',
        'expense_date' => now()->toDateString(),
        'payment_method_id' => $method->id,
        'note' => 'Monthly utility bill',
        'recorded_by' => $cashier->getKey(),
    ]);

    $movement = CashMovement::query()->create([
        'register_session_id' => $session->id,
        'tenant_id' => $tenant->id,
        'type' => 'cash_out',
        'amount' => '240.0000',
        'reason' => 'Utility payment',
        'recorded_by' => $cashier->getKey(),
    ]);

    expect($category->name)->toBe('Utilities')
        ->and($expense->amount)->toBe('240.0000')
        ->and($expense->payment_method_id)->toBe($method->id)
        ->and($movement->type)->toBe('cash_out')
        ->and($movement->amount)->toBe('240.0000');
});
