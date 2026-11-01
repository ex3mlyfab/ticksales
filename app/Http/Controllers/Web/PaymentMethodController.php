<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = $request->attributes->get('tenant');

        $methods = PaymentMethod::query()
            ->where('tenant_id', $tenant?->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('payment-methods/index', [
            'methods' => $methods,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('payment-methods/create');
    }

    public function store(StorePaymentMethodRequest $request, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');
        $validated = $request->validated();

        $method = PaymentMethod::query()->create([
            'tenant_id' => $tenant?->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_active' => $validated['is_active'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $auditService->created($method, ['event' => 'payment_method.created']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method created.')]);

        return to_route('payment-methods.index');
    }

    public function edit(Request $request, PaymentMethod $paymentMethod): Response
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($paymentMethod->tenant_id === $tenant?->id, 403);

        return Inertia::render('payment-methods/edit', [
            'method' => $paymentMethod,
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($paymentMethod->tenant_id === $tenant?->id, 403);

        $validated = $request->validated();

        $paymentMethod->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_active' => $validated['is_active'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $auditService->updated($paymentMethod, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method updated.')]);

        return to_route('payment-methods.index');
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod, AuditService $auditService): RedirectResponse
    {
        $tenant = $request->attributes->get('tenant');

        abort_unless($paymentMethod->tenant_id === $tenant?->id, 403);

        if ($paymentMethod->salePayments()->exists()) {
            return to_route('payment-methods.index')
                ->with('error', 'Cannot delete payment method used in sales. Deactivate it instead.');
        }

        $auditService->deleted($paymentMethod);

        $paymentMethod->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment method deleted.')]);

        return to_route('payment-methods.index');
    }
}
