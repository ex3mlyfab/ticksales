<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/plans/index', [
            'plans' => SubscriptionPlan::query()
                ->withCount(['tenants', 'subscriptionPayments'])
                ->orderBy('price_monthly')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/plans/create');
    }

    public function store(Request $request, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:subscription_plans,name'],
            ...$this->planRules(),
        ]);
        $validated['features'] = $this->features($validated['features'] ?? null);

        $plan = SubscriptionPlan::query()->create($validated);

        $auditService->log(
            event: 'subscription_plan.created',
            auditableType: SubscriptionPlan::class,
            auditableId: $plan->id,
            newValues: $plan->getAttributes(),
        );

        return redirect()->route('admin.plans.index')->with('success', 'Subscription plan created.');
    }

    public function edit(SubscriptionPlan $plan): Response
    {
        return Inertia::render('admin/plans/edit', [
            'plan' => [
                ...$plan->toArray(),
                'features_text' => implode("\n", $plan->features ?? []),
            ],
        ]);
    }

    public function update(Request $request, SubscriptionPlan $plan, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate($this->planRules());
        $validated['features'] = $this->features($validated['features'] ?? null);

        $oldValues = $plan->only(array_keys($validated));
        $plan->update($validated);

        $auditService->log(
            event: 'subscription_plan.updated',
            auditableType: SubscriptionPlan::class,
            auditableId: $plan->id,
            oldValues: $oldValues,
            newValues: $plan->only(array_keys($validated)),
        );

        return redirect()->route('admin.plans.index')->with('success', 'Subscription plan updated.');
    }

    public function destroy(SubscriptionPlan $plan, AuditService $auditService): RedirectResponse
    {
        if ($plan->tenants()->exists() || $plan->subscriptionPayments()->exists()) {
            return back()->with('error', 'Plans assigned to tenants or referenced by payments cannot be deleted. Deactivate the plan instead.');
        }

        $auditService->log(
            event: 'subscription_plan.deleted',
            auditableType: SubscriptionPlan::class,
            auditableId: $plan->id,
            oldValues: $plan->getAttributes(),
        );

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'Subscription plan deleted.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function planRules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_monthly' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'max_branches' => ['required', 'integer', 'min:1', 'max:1000000'],
            'features' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function features(?string $features): array
    {
        if ($features === null || trim($features) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('trim', preg_split('/\R/u', $features) ?: []),
            static fn (string $feature): bool => $feature !== '',
        )));
    }
}
