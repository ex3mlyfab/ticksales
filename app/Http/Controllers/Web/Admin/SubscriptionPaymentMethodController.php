<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPaymentMethod;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPaymentMethodController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/subscription-payment-methods/index', [
            'methods' => SubscriptionPaymentMethod::query()
                ->withCount('subscriptionPayments')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/subscription-payment-methods/create');
    }

    public function store(Request $request, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate($this->methodRules());
        $method = SubscriptionPaymentMethod::query()->create($validated);

        $auditService->log(
            event: 'subscription_payment_method.created',
            auditableType: SubscriptionPaymentMethod::class,
            auditableId: $method->id,
            newValues: $method->getAttributes(),
        );

        return redirect()->route('admin.subscription-payment-methods.index')
            ->with('success', 'Subscription payment method created.');
    }

    public function edit(SubscriptionPaymentMethod $subscriptionPaymentMethod): Response
    {
        return Inertia::render('admin/subscription-payment-methods/edit', [
            'method' => $subscriptionPaymentMethod,
        ]);
    }

    public function update(
        Request $request,
        SubscriptionPaymentMethod $subscriptionPaymentMethod,
        AuditService $auditService,
    ): RedirectResponse {
        $validated = $request->validate($this->methodRules());
        $oldValues = $subscriptionPaymentMethod->only(array_keys($validated));
        $subscriptionPaymentMethod->update($validated);

        $auditService->log(
            event: 'subscription_payment_method.updated',
            auditableType: SubscriptionPaymentMethod::class,
            auditableId: $subscriptionPaymentMethod->id,
            oldValues: $oldValues,
            newValues: $subscriptionPaymentMethod->only(array_keys($validated)),
        );

        return redirect()->route('admin.subscription-payment-methods.index')
            ->with('success', 'Subscription payment method updated.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function methodRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'instructions' => ['required', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
