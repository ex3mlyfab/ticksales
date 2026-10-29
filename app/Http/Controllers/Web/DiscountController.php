<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Discount\StoreDiscountRequest;
use App\Http\Requests\Discount\UpdateDiscountRequest;
use App\Models\Discount;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    /**
     * Display a listing of discounts.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $discounts = Discount::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('discounts/index', [
            'discounts' => $discounts,
        ]);
    }

    /**
     * Show the form for creating a new discount.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('discounts/create');
    }

    /**
     * Store a newly created discount.
     */
    public function store(StoreDiscountRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $discount = Discount::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_purchase_amount' => $validated['min_purchase_amount'] ?? null,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $auditService->created($discount, ['event' => 'discount.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Discount created.')]);

        return to_route('discounts.index');
    }

    /**
     * Show the form for editing the discount.
     */
    public function edit(Request $request, Discount $discount): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($discount->tenant_id === $tenant?->id, 403);

        return Inertia::render('discounts/edit', [
            'discount' => $discount,
        ]);
    }

    /**
     * Update the discount.
     */
    public function update(UpdateDiscountRequest $request, Discount $discount, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($discount->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $discount->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_purchase_amount' => $validated['min_purchase_amount'] ?? null,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        $auditService->updated($discount, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Discount updated.')]);

        return to_route('discounts.index');
    }

    /**
     * Remove the discount.
     */
    public function destroy(Request $request, Discount $discount, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($discount->tenant_id === $tenant?->id, 403);

        $auditService->deleted($discount);

        $discount->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Discount deleted.')]);

        return to_route('discounts.index');
    }
}
