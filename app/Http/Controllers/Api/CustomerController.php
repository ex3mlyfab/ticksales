<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display the customers for the active tenant.
     */
    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $customers = Customer::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Customer $customer) => $this->serializeCustomer($customer));

        return response()->json([
            'data' => $customers,
        ]);
    }

    /**
     * Display the specified customer.
     */
    public function show(Request $request, string $customer)
    {
        $tenant = $request->attributes->get('tenant');

        $customerModel = Customer::query()
            ->where('tenant_id', $tenant?->id)
            ->findOrFail($customer);

        return response()->json([
            'data' => $this->serializeCustomer($customerModel),
        ]);
    }

    /**
     * Store a newly created customer in the active tenant.
     */
    public function store(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->where(fn ($query) => $query->where('tenant_id', $tenant?->id))],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'customer_group_id' => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric'],
            'loyalty_points' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant?->id,
            ...$validated,
        ]);

        return response()->json([
            'data' => $this->serializeCustomer($customer),
        ], 201);
    }

    /**
     * Update the specified customer in the active tenant.
     */
    public function update(Request $request, string $customer)
    {
        $tenant = $request->attributes->get('tenant');

        $customerModel = Customer::query()
            ->where('tenant_id', $tenant?->id)
            ->findOrFail($customer);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customerModel->id)->where(fn ($query) => $query->where('tenant_id', $tenant?->id))],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'customer_group_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credit_limit' => ['sometimes', 'nullable', 'numeric'],
            'loyalty_points' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customerModel->fill($validated);
        $customerModel->save();

        return response()->json([
            'data' => $this->serializeCustomer($customerModel->refresh()),
        ]);
    }

    /**
     * Serialize a customer for API responses.
     *
     * @return array<string, mixed>
     */
    protected function serializeCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'tenant_id' => $customer->tenant_id,
            'customer_group_id' => $customer->customer_group_id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'credit_limit' => $customer->credit_limit,
            'outstanding_balance' => $customer->outstanding_balance,
            'loyalty_points' => $customer->loyalty_points,
            'is_active' => $customer->is_active,
        ];
    }
}
