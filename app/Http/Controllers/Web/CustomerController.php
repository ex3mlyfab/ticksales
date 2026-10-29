<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $customers = Customer::query()
            ->where('tenant_id', $tenant?->id)
            ->with('customerGroup')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('customers/index', [
            'customers' => $customers,
        ]);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $groups = CustomerGroup::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('customers/create', [
            'groups' => $groups,
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $customer = Customer::query()->create([
            'tenant_id' => $tenant?->id,
            'customer_group_id' => $validated['customer_group_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'credit_limit' => $validated['credit_limit'],
            'loyalty_points' => $validated['loyalty_points'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);

        $auditService->created($customer, ['event' => 'customer.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer created.')]);

        return to_route('customers.index');
    }

    /**
     * Show the form for editing the customer.
     */
    public function edit(Request $request, Customer $customer): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($customer->tenant_id === $tenant?->id, 403);

        $groups = CustomerGroup::query()
            ->where('tenant_id', $tenant?->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('customers/edit', [
            'customer' => $customer,
            'groups' => $groups,
        ]);
    }

    /**
     * Update the customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($customer->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $customer->update([
            'customer_group_id' => $validated['customer_group_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'credit_limit' => $validated['credit_limit'],
            'loyalty_points' => $validated['loyalty_points'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);

        $auditService->updated($customer, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer updated.')]);

        return to_route('customers.index');
    }

    /**
     * Remove the customer.
     */
    public function destroy(Request $request, Customer $customer, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($customer->tenant_id === $tenant?->id, 403);

        $auditService->deleted($customer);

        $customer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer deleted.')]);

        return to_route('customers.index');
    }
}
