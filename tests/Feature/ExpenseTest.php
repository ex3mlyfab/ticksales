<?php

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected from expense routes', function () {
    get('/expenses')->assertRedirect('/login');
    get('/expense-categories')->assertRedirect('/login');
});

test('expense categories can be created via web controller', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/expense-categories', [
            'name' => 'Utilities',
        ])
        ->assertRedirect(route('expense-categories.index'));

    $category = ExpenseCategory::query()->where('name', 'Utilities')->first();

    expect($category)->not->toBeNull()
        ->and($category->tenant_id)->toBe($tenant->id);
});

test('expense category update and soft boundaries work', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/expense-categories/'.$category->id, [
            'name' => 'Renamed Category',
        ])
        ->assertRedirect(route('expense-categories.index'));

    expect($category->fresh()->name)->toBe('Renamed Category');
});

test('expense category delete is blocked when expenses exist', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);

    Expense::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'expense_category_id' => $category->id,
        'recorded_by' => $user->id,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/expense-categories/'.$category->id)
        ->assertRedirect(route('expense-categories.index'));

    expect(ExpenseCategory::query()->where('id', $category->id)->exists())->toBeTrue();
});

test('expense category delete succeeds when no expenses exist', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/expense-categories/'.$category->id);

    expect(ExpenseCategory::query()->where('id', $category->id)->exists())->toBeFalse();
});

test('cross-tenant expense category operations return 403', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenantA);

    $foreignCategory = ExpenseCategory::factory()->create(['tenant_id' => $tenantB->id]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->put('/expense-categories/'.$foreignCategory->id, ['name' => 'Hijacked'])
        ->assertForbidden();

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->delete('/expense-categories/'.$foreignCategory->id)
        ->assertForbidden();
});

test('expense can be recorded via web controller', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);
    $paymentMethod = PaymentMethod::factory()->create([
        'tenant_id' => $tenant->id,
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/expenses', [
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => 'Electricity bill',
            'amount' => 125.50,
            'expense_date' => now()->toDateString(),
            'payment_method_id' => $paymentMethod->id,
        ]);

    $expense = Expense::query()->where('description', 'Electricity bill')->first();

    expect($expense)->not->toBeNull()
        ->and($expense->tenant_id)->toBe($tenant->id)
        ->and($expense->branch_id)->toBe($branch->id)
        ->and($expense->recorded_by)->toBe($user->id)
        ->and($expense->amount)->toBe('125.5000')
        ->and($expense->reference_no)->not->toBeNull()
        ->and($expense->reference_no)->toStartWith('EXP-'.now()->format('Y-m-d'));
});

test('expense reference_no is auto-generated and sequential', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/expenses', [
            'branch_id' => $branch->id,
            'description' => 'Office supplies',
            'amount' => 10,
            'expense_date' => now()->toDateString(),
        ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/expenses', [
            'branch_id' => $branch->id,
            'description' => 'Cleaning',
            'amount' => 40,
            'expense_date' => now()->toDateString(),
        ]);

    $refs = Expense::query()->where('tenant_id', $tenant->id)->orderBy('created_at')->pluck('reference_no')->all();

    expect($refs[0])->toContain('-000001')
        ->and($refs[1])->toContain('-000002');
});

test('expense can be updated and deleted', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $expense = Expense::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'recorded_by' => $user->id,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/expenses/'.$expense->id, [
            'branch_id' => $branch->id,
            'description' => 'Updated description',
            'amount' => 999.99,
            'expense_date' => now()->toDateString(),
        ])
        ->assertRedirect(route('expenses.show', $expense));

    expect($expense->fresh()->description)->toBe('Updated description')
        ->and($expense->fresh()->amount)->toBe('999.9900');

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->delete('/expenses/'.$expense->id);

    expect(Expense::query()->where('id', $expense->id)->exists())->toBeFalse();
});

test('cross-tenant expense operations return 403', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $branchB = Branch::factory()->create(['tenant_id' => $tenantB->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenantA);

    $foreignExpense = Expense::factory()->create([
        'tenant_id' => $tenantB->id,
        'branch_id' => $branchB->id,
        'recorded_by' => $user->id,
    ]);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->get('/expenses/'.$foreignExpense->id)
        ->assertForbidden();

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->put('/expenses/'.$foreignExpense->id, [
            'branch_id' => $branchB->id,
            'description' => 'Hijacked',
            'amount' => 1,
            'expense_date' => now()->toDateString(),
        ])
        ->assertForbidden();

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenantA->id])
        ->delete('/expenses/'.$foreignExpense->id)
        ->assertForbidden();
});

test('expense pages render for an authenticated tenant user', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $expense = Expense::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'recorded_by' => $user->id,
    ]);

    actingAs($user)->withSession(['active_tenant_id' => $tenant->id]);

    get('/expenses')->assertOk();
    get('/expenses/create')->assertOk();
    get('/expenses/'.$expense->id)->assertOk();
    get('/expenses/'.$expense->id.'/edit')->assertOk();
});

test('expense category pages render for an authenticated tenant user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    $category = ExpenseCategory::factory()->create(['tenant_id' => $tenant->id]);

    actingAs($user)->withSession(['active_tenant_id' => $tenant->id]);

    get('/expense-categories')->assertOk();
    get('/expense-categories/create')->assertOk();
    get('/expense-categories/'.$category->id.'/edit')->assertOk();
});
