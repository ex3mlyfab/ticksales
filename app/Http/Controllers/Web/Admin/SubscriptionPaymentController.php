<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Support\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubscriptionPaymentController extends Controller
{
    public function store(
        Request $request,
        Tenant $tenant,
        AuditService $auditService,
    ): RedirectResponse {
        $validated = $request->validate([
            'plan_id' => ['required', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'payment_method_id' => ['required', Rule::exists('subscription_payment_methods', 'id')->where('is_active', true)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'confirmed' => ['accepted'],
        ]);

        DB::transaction(function () use ($validated, $tenant, $request, $auditService): void {
            $lockedTenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $plan = SubscriptionPlan::query()
                ->whereKey($validated['plan_id'])
                ->where('is_active', true)
                ->firstOrFail();
            $paymentMethod = SubscriptionPaymentMethod::query()
                ->whereKey($validated['payment_method_id'])
                ->where('is_active', true)
                ->firstOrFail();
            $paidAt = Carbon::parse($validated['paid_at'])->startOfDay();
            $periodStartsAt = $lockedTenant->subscription_ends_at?->isFuture()
                ? $lockedTenant->subscription_ends_at->copy()->addSecond()
                : $paidAt->copy();
            $periodEndsAt = $periodStartsAt->copy()->addMonthNoOverflow();
            if ($periodStartsAt->isLastOfMonth()) {
                $periodEndsAt->endOfMonth();
            } elseif ($periodStartsAt->day === $periodEndsAt->day) {
                $periodEndsAt->subDay()->endOfDay();
            } else {
                $periodEndsAt->endOfDay();
            }

            $payment = SubscriptionPayment::query()->create([
                'tenant_id' => $lockedTenant->id,
                'plan_id' => $plan->id,
                'payment_method_id' => $paymentMethod->id,
                'recorded_by' => $request->user()->id,
                'payment_method_name' => $paymentMethod->name,
                'amount' => $validated['amount'],
                'currency' => $lockedTenant->currency,
                'reference' => $validated['reference'] ?? null,
                'paid_at' => $paidAt,
                'period_starts_at' => $periodStartsAt,
                'period_ends_at' => $periodEndsAt,
                'notes' => $validated['notes'] ?? null,
            ]);

            $lockedTenant->forceFill([
                'plan' => $plan->name,
                'trial_ends_at' => null,
                'subscription_ends_at' => $periodEndsAt,
            ])->save();

            $auditService->log(
                event: 'subscription_payment.recorded',
                auditableType: SubscriptionPayment::class,
                auditableId: $payment->id,
                newValues: $payment->getAttributes(),
                tenant: $lockedTenant,
            );
        });

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Payment recorded and the subscription renewed for one month.');
    }
}
