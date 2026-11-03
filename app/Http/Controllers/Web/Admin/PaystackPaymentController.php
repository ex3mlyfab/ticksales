<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionCheckout;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AuditService;
use App\Support\Payments\PaystackPaymentException;
use App\Support\Payments\PaystackSubscriptionCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaystackPaymentController extends Controller
{
    public function store(
        Request $request,
        Tenant $tenant,
        PaystackSubscriptionCheckout $paystack,
        AuditService $auditService,
    ): RedirectResponse {
        $validated = $request->validate([
            'plan_id' => ['required', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
        ]);
        $plan = SubscriptionPlan::query()
            ->whereKey($validated['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        try {
            $checkout = $paystack->initialize($tenant, $plan, $admin);
        } catch (PaystackPaymentException $exception) {
            report($exception);

            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        $auditService->log(
            event: 'subscription_checkout.started',
            auditableType: SubscriptionCheckout::class,
            auditableId: $checkout->id,
            newValues: $checkout->only(['reference', 'amount', 'currency', 'plan_id']),
            tenant: $tenant,
        );

        return redirect()->away($checkout->checkout_url);
    }

    public function callback(
        Request $request,
        PaystackSubscriptionCheckout $paystack,
        AuditService $auditService,
    ): RedirectResponse {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
        ]);
        $checkout = $paystack->findByReference($validated['reference']);
        $admin = $request->user();

        abort_if($checkout === null, 404);
        abort_unless($admin instanceof User && $checkout->initiated_by === $admin->id, 403);

        try {
            $checkout = $paystack->verifyAndComplete($checkout->reference, $auditService);
        } catch (PaystackPaymentException $exception) {
            report($exception);

            return redirect()->route('admin.tenants.show', $checkout->tenant_id)
                ->with('error', $exception->getMessage());
        }

        if ($checkout->status === SubscriptionCheckout::STATUS_PAID) {
            return redirect()->route('admin.tenants.show', $checkout->tenant_id)
                ->with('success', 'Paystack payment verified and the subscription renewed.');
        }

        return redirect()->route('admin.tenants.show', $checkout->tenant_id)
            ->with('error', 'Paystack has not confirmed this payment. The subscription was not changed.');
    }

    public function webhook(
        Request $request,
        PaystackSubscriptionCheckout $paystack,
        AuditService $auditService,
    ): JsonResponse {
        $secret = config('services.paystack.secret');
        abort_unless(is_string($secret) && $secret !== '', 503, 'Paystack webhook is not configured.');

        $signature = $request->header('x-paystack-signature');
        abort_unless(
            is_string($signature)
                && hash_equals(hash_hmac('sha512', $request->getContent(), $secret), $signature),
            401,
            'Invalid Paystack webhook signature.',
        );

        if ($request->input('event') !== 'charge.success') {
            return response()->json(['received' => true]);
        }

        $reference = data_get($request->input('data'), 'reference');
        if (! is_string($reference)) {
            return response()->json(['received' => false], 422);
        }

        if ($paystack->findByReference($reference) === null) {
            return response()->json(['received' => true]);
        }

        $paystack->verifyAndComplete($reference, $auditService);

        return response()->json(['received' => true]);
    }
}
