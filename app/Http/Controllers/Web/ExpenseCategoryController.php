<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseCategory\StoreExpenseCategoryRequest;
use App\Http\Requests\ExpenseCategory\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $categories = ExpenseCategory::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->withCount('expenses')
            ->paginate(20);

        return Inertia::render('expense-categories/index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('expense-categories/create');
    }

    public function store(StoreExpenseCategoryRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $category = ExpenseCategory::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
        ]);

        $auditService->created($category, ['event' => 'expense_category.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense category created.')]);

        return to_route('expense-categories.index');
    }

    public function edit(Request $request, ExpenseCategory $expenseCategory): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($expenseCategory->tenant_id === $tenant?->id, 403);

        return Inertia::render('expense-categories/edit', [
            'category' => $expenseCategory,
        ]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($expenseCategory->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $expenseCategory->update([
            'name' => $validated['name'],
        ]);

        $auditService->updated($expenseCategory, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense category updated.')]);

        return to_route('expense-categories.index');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($expenseCategory->tenant_id === $tenant?->id, 403);

        if ($expenseCategory->expenses()->exists()) {
            return to_route('expense-categories.index')
                ->with('error', 'Cannot delete an expense category that has expenses. Reassign them first.');
        }

        $auditService->deleted($expenseCategory);

        $expenseCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense category deleted.')]);

        return to_route('expense-categories.index');
    }
}
