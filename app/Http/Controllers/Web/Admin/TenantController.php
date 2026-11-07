<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Support\AuditService;
use App\Support\Payments\PaystackSubscriptionCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(): Response
    {
        return Inertia::render('admin/tenants/index', [
            'tenants' => Tenant::query()
                ->with('subscriptionPlan:id,name,display_name')
                ->latest()
                ->get()
                ->map(fn (Tenant $tenant) => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'email' => $tenant->email,
                    'is_active' => (bool) $tenant->is_active,
                    'plan' => $tenant->plan,
                    'plan_name' => $tenant->subscriptionPlan?->display_name,
                    'subscription_status' => $tenant->status,
                    'created_at' => $tenant->created_at?->toDateTimeString(),
                ]),
        ]);
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(): Response
    {
        return Inertia::render('admin/tenants/create', [
            'plans' => SubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('price_monthly')
                ->get(['name', 'display_name', 'is_active']),
        ]);
    }

    /**
     * Store a newly created tenant in storage.
     */
    public function store(Request $request, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tenants,slug'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:2'],
            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'plan' => ['nullable', 'string', Rule::exists('subscription_plans', 'name')->where('is_active', true)],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['currency'] = strtoupper($validated['currency'] ?? 'NGN');

        $tenant = Tenant::query()->create($validated);

        $auditService->log(
            event: 'tenant.created',
            auditableType: Tenant::class,
            auditableId: $tenant->id,
            newValues: $tenant->getAttributes(),
            tenant: $tenant,
        );

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant created successfully.');
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant, PaystackSubscriptionCheckout $paystack): Response
    {
        $tenant->load('subscriptionPlan:id,name,display_name,price_monthly');

        return Inertia::render('admin/tenants/show', [
            'tenant' => [
                ...$tenant->only([
                    'id',
                    'name',
                    'slug',
                    'email',
                    'phone',
                    'address',
                    'country',
                    'currency',
                    'timezone',
                    'locale',
                    'is_active',
                    'plan',
                    'created_at',
                ]),
                'plan_name' => $tenant->subscriptionPlan?->display_name,
                'subscription_status' => $tenant->status,
                'subscription_ends_at' => $tenant->subscription_ends_at?->toDateString(),
            ],
            'plans' => SubscriptionPlan::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('name', $tenant->plan))
                ->orderBy('price_monthly')
                ->get(['id', 'name', 'display_name', 'price_monthly', 'is_active']),
            'payment_methods' => SubscriptionPaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'instructions']),
            'paystack_enabled' => $paystack->isEnabled(),
            'paystack_currency_supported' => $paystack->supportsCurrency($tenant->currency),
            'pending_checkouts' => $tenant->subscriptionCheckouts()
                ->where('status', 'pending')
                ->with('plan:id,display_name')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($checkout) => [
                    'reference' => $checkout->reference,
                    'plan' => $checkout->plan?->display_name,
                    'amount' => $checkout->amount,
                    'currency' => $checkout->currency,
                    'checkout_url' => $checkout->checkout_url,
                    'created_at' => $checkout->created_at?->toDateTimeString(),
                ]),
            'payments' => $tenant->subscriptionPayments()
                ->with('plan:id,display_name')
                ->latest('paid_at')
                ->get()
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'plan' => $payment->plan?->display_name,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method_name,
                    'reference' => $payment->reference,
                    'paid_at' => $payment->paid_at->toDateString(),
                    'period_ends_at' => $payment->period_ends_at->toDateString(),
                    'notes' => $payment->notes,
                ]),
        ]);
    }

    /**
     * Show the form for editing the specified tenant.
     */
    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('admin/tenants/edit', [
            'tenant' => $tenant->only([
                'id',
                'name',
                'slug',
                'email',
                'phone',
                'address',
                'country',
                'currency',
                'timezone',
                'locale',
                'is_active',
                'plan',
            ]),
            'plans' => SubscriptionPlan::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('name', $tenant->plan))
                ->orderBy('price_monthly')
                ->get(['name', 'display_name', 'is_active']),
        ]);
    }

    /**
     * Update the specified tenant in storage.
     */
    public function update(Request $request, Tenant $tenant, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tenants,slug,'.$tenant->id],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:2'],
            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'plan' => ['nullable', 'string', Rule::exists('subscription_plans', 'name')->where(
                fn ($query) => $query->where('is_active', true)->orWhere('name', $tenant->plan),
            )],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        $tenant->update($validated);

        $auditService->log(
            event: 'tenant.updated',
            auditableType: Tenant::class,
            auditableId: $tenant->id,
            oldValues: array_intersect_key($tenant->getOriginal(), $tenant->getChanges()),
            newValues: $tenant->getChanges(),
            tenant: $tenant,
        );

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Remove the specified tenant from storage.
     */
    public function destroy(Tenant $tenant, AuditService $auditService): RedirectResponse
    {
        $auditService->log(
            event: 'tenant.deleted',
            auditableType: Tenant::class,
            auditableId: $tenant->id,
            oldValues: $tenant->getAttributes(),
            tenant: $tenant,
        );

        $tenant->delete();

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }
}
