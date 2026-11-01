<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $expenses = Expense::query()
            ->where('tenant_id', $tenant?->id)
            ->with(['branch', 'expenseCategory', 'paymentMethod', 'recordedBy'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('expenses/index', [
            'expenses' => $expenses,
        ]);
    }

    public function create(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = ExpenseCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PaymentMethod::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        return Inertia::render('expenses/create', [
            'branches' => $branches,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(StoreExpenseRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();
        $validated = $request->validated();

        $expense = Expense::query()->create([
            'tenant_id' => $tenant?->id,
            'branch_id' => $validated['branch_id'],
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'reference_no' => $validated['reference_no'] ?? $this->nextReference($tenant?->id),
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'note' => $validated['note'] ?? null,
            'attachment_path' => $validated['attachment_path'] ?? null,
            'recorded_by' => $user?->id,
        ]);

        $auditService->created($expense, ['event' => 'expense.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense recorded.')]);

        return to_route('expenses.show', $expense);
    }

    public function show(Request $request, Expense $expense): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($expense->tenant_id === $tenant?->id, 403);

        $expense->load(['branch', 'expenseCategory', 'paymentMethod', 'recordedBy']);

        return Inertia::render('expenses/show', [
            'expense' => $expense,
        ]);
    }

    public function edit(Request $request, Expense $expense): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($expense->tenant_id === $tenant?->id, 403);

        $branches = Branch::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = ExpenseCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PaymentMethod::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'type']);

        $expense->load(['branch', 'expenseCategory', 'paymentMethod']);

        return Inertia::render('expenses/edit', [
            'expense' => $expense,
            'branches' => $branches,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($expense->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $expense->update([
            'branch_id' => $validated['branch_id'],
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'reference_no' => $validated['reference_no'] ?? $expense->reference_no,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'note' => $validated['note'] ?? null,
            'attachment_path' => $validated['attachment_path'] ?? null,
        ]);

        $auditService->updated($expense, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense updated.')]);

        return to_route('expenses.show', $expense);
    }

    public function destroy(Request $request, Expense $expense, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($expense->tenant_id === $tenant?->id, 403);

        $auditService->deleted($expense);

        $expense->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense deleted.')]);

        return to_route('expenses.index');
    }

    /**
     * Generate the next sequential reference for the tenant, e.g. EXP-2026-10-30-000001.
     */
    private function nextReference(?string $tenantId): string
    {
        $prefix = 'EXP-'.now()->format('Y-m-d');

        $count = Expense::query()
            ->where('tenant_id', $tenantId)
            ->where('reference_no', 'like', $prefix.'%')
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
