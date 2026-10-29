<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerGroupRequest;
use App\Http\Requests\Customer\UpdateCustomerGroupRequest;
use App\Models\CustomerGroup;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerGroupController extends Controller
{
    /**
     * Display a listing of customer groups.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $groups = CustomerGroup::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('customer-groups/index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Show the form for creating a new customer group.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('customer-groups/create');
    }

    /**
     * Store a newly created customer group.
     */
    public function store(StoreCustomerGroupRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $group = CustomerGroup::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'discount_rate' => $validated['discount_rate'],
            'is_active' => $validated['is_active'],
        ]);

        $auditService->created($group, ['event' => 'customer_group.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer group created.')]);

        return to_route('customer-groups.index');
    }

    /**
     * Show the form for editing the customer group.
     */
    public function edit(Request $request, CustomerGroup $customerGroup): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($customerGroup->tenant_id === $tenant?->id, 403);

        return Inertia::render('customer-groups/edit', [
            'group' => $customerGroup,
        ]);
    }

    /**
     * Update the customer group.
     */
    public function update(UpdateCustomerGroupRequest $request, CustomerGroup $customerGroup, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($customerGroup->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $customerGroup->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'discount_rate' => $validated['discount_rate'],
            'is_active' => $validated['is_active'],
        ]);

        $auditService->updated($customerGroup, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer group updated.')]);

        return to_route('customer-groups.index');
    }

    /**
     * Remove the customer group.
     */
    public function destroy(Request $request, CustomerGroup $customerGroup, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($customerGroup->tenant_id === $tenant?->id, 403);

        // Cannot delete if customers are in this group
        if ($customerGroup->customers()->exists()) {
            return to_route('customer-groups.index')
                ->with('error', 'Cannot delete customer group that has associated customers.');
        }

        $auditService->deleted($customerGroup);

        $customerGroup->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer group deleted.')]);

        return to_route('customer-groups.index');
    }
}
