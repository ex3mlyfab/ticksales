<?php

namespace App\Support\Payments;

use App\Models\SubscriptionCheckout;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AuditService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaystackSubscriptionCheckout
{
    private const API_URL = 'https://api.paystack.co';

    /**
     * Paystack expects transaction amounts in the currency's minor unit.
     *
     * @var array<string, int>
     */
    private const CURRENCY_EXPONENTS = [
        'NGN' => 2,
        'GHS' => 2,
        'ZAR' => 2,
        'USD' => 2,
        'KES' => 2,
        'XOF' => 0,
        'RWF' => 0,
        'TZS' => 2,
        'UGX' => 0,
        'ZMW' => 2,
        'XAF' => 0,
        'EGP' => 2,
    ];

    public function isEnabled(): bool
    {
        return (bool) config('services.paystack.enabled')
            && is_string(config('services.paystack.secret'))
            && config('services.paystack.secret') !== '';
    }

    public function supportsCurrency(string $currency): bool
    {
        return array_key_exists(strtoupper($currency), self::CURRENCY_EXPONENTS);
    }

    public function initialize(Tenant $tenant, SubscriptionPlan $plan, User $admin): SubscriptionCheckout
    {
        if (! $this->isEnabled()) {
            throw ValidationException::withMessages([
                'payment' => 'Paystack checkout is not configured. Ask your deployment administrator to enable it.',
            ]);
        }

        $currency = strtoupper($tenant->currency);
        if (! $this->supportsCurrency($currency)) {
            throw ValidationException::withMessages([
                'payment' => "Paystack checkout does not support the tenant's {$currency} currency.",
            ]);
        }

        if (! filter_var($tenant->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'payment' => 'Add a valid billing email to the tenant before starting checkout.',
            ]);
        }

        $minorUnits = $this->minorUnits($plan->price_monthly, $currency);
        $reference = 'TS-'.Str::ulid();
        $checkout = SubscriptionCheckout::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'initiated_by' => $admin->id,
            'reference' => $reference,
            'status' => SubscriptionCheckout::STATUS_PENDING,
            'amount' => $plan->price_monthly,
            'currency' => $currency,
        ]);

        try {
            $response = Http::acceptJson()
                ->withToken((string) config('services.paystack.secret'))
                ->connectTimeout(5)
                ->timeout(20)
                ->post(self::API_URL.'/transaction/initialize', [
                    'email' => $tenant->email,
                    'amount' => $minorUnits,
                    'currency' => $currency,
                    'reference' => $reference,
                    'callback_url' => route('admin.paystack.callback'),
                    'metadata' => [
                        'subscription_checkout_id' => $checkout->id,
                        'tenant_id' => $tenant->id,
                        'plan_id' => $plan->id,
                    ],
                ]);
        } catch (ConnectionException $exception) {
            $checkout->update(['status' => SubscriptionCheckout::STATUS_FAILED]);

            throw new PaystackPaymentException('Paystack could not be reached. Please try again.', previous: $exception);
        }

        $payload = $response->json();
        $authorizationUrl = is_array($payload) ? data_get($payload, 'data.authorization_url') : null;
        $providerReference = is_array($payload) ? data_get($payload, 'data.reference') : null;

        if (! $response->successful() || data_get($payload, 'status') !== true
            || ! is_string($authorizationUrl) || ! str_starts_with($authorizationUrl, 'https://')
            || $providerReference !== $reference) {
            $checkout->update(['status' => SubscriptionCheckout::STATUS_FAILED]);

            throw new PaystackPaymentException('Paystack did not create a checkout session. Check the gateway configuration and try again.');
        }

        $checkout->update(['checkout_url' => $authorizationUrl]);

        return $checkout->refresh();
    }

    public function findByReference(string $reference): ?SubscriptionCheckout
    {
        return SubscriptionCheckout::query()
            ->with(['tenant', 'initiator'])
            ->where('reference', $reference)
            ->first();
    }

    public function verifyAndComplete(string $reference, AuditService $auditService): SubscriptionCheckout
    {
        $checkout = SubscriptionCheckout::query()
            ->where('reference', $reference)
            ->firstOrFail();

        if ($checkout->status === SubscriptionCheckout::STATUS_PAID) {
            return $checkout;
        }

        if (! $this->isEnabled()) {
            throw new PaystackPaymentException('Paystack verification is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken((string) config('services.paystack.secret'))
                ->connectTimeout(5)
                ->timeout(20)
                ->get(self::API_URL.'/transaction/verify/'.rawurlencode($reference));
        } catch (ConnectionException $exception) {
            throw new PaystackPaymentException('Paystack could not be reached to verify this payment.', previous: $exception);
        }

        $payload = $response->json();
        if (! $response->successful() || data_get($payload, 'status') !== true || ! is_array(data_get($payload, 'data'))) {
            throw new PaystackPaymentException('Paystack could not verify this transaction.');
        }

        $transaction = $payload['data'];
        $providerStatus = data_get($transaction, 'status');
        if ($providerStatus !== 'success') {
            if (in_array($providerStatus, ['failed', 'abandoned', 'reversed'], true)) {
                $checkout->update(['status' => SubscriptionCheckout::STATUS_FAILED]);
            }

            return $checkout->refresh();
        }

        if (data_get($transaction, 'reference') !== $checkout->reference
            || strtoupper((string) data_get($transaction, 'currency')) !== $checkout->currency
            || (int) data_get($transaction, 'amount') !== $this->minorUnits($checkout->amount, $checkout->currency)) {
            $checkout->update(['status' => SubscriptionCheckout::STATUS_FAILED]);

            throw new PaystackPaymentException('The verified Paystack transaction did not match the checkout reference, amount, or currency.');
        }

        return DB::transaction(function () use ($checkout, $transaction, $auditService): SubscriptionCheckout {
            $lockedCheckout = SubscriptionCheckout::query()
                ->with(['tenant', 'plan'])
                ->lockForUpdate()
                ->findOrFail($checkout->id);

            if ($lockedCheckout->status === SubscriptionCheckout::STATUS_PAID) {
                return $lockedCheckout;
            }

            $tenant = Tenant::query()->lockForUpdate()->findOrFail($lockedCheckout->tenant_id);
            $plan = SubscriptionPlan::query()->findOrFail($lockedCheckout->plan_id);
            $paidAt = is_string(data_get($transaction, 'paid_at'))
                ? Carbon::parse(data_get($transaction, 'paid_at'))
                : now();
            $periodStartsAt = $tenant->subscription_ends_at?->isFuture()
                ? $tenant->subscription_ends_at->copy()->addSecond()
                : $paidAt->copy();
            $periodEndsAt = $this->periodEnd($periodStartsAt);
            $transactionId = (string) data_get($transaction, 'id');

            $payment = SubscriptionPayment::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'recorded_by' => $lockedCheckout->initiated_by,
                'payment_method_name' => 'Paystack',
                'gateway' => 'paystack',
                'gateway_transaction_id' => $transactionId,
                'amount' => $lockedCheckout->amount,
                'currency' => $lockedCheckout->currency,
                'reference' => $lockedCheckout->reference,
                'paid_at' => $paidAt,
                'period_starts_at' => $periodStartsAt,
                'period_ends_at' => $periodEndsAt,
            ]);

            $tenant->forceFill([
                'plan' => $plan->name,
                'trial_ends_at' => null,
                'subscription_ends_at' => $periodEndsAt,
            ])->save();

            $lockedCheckout->update([
                'status' => SubscriptionCheckout::STATUS_PAID,
                'subscription_payment_id' => $payment->id,
                'gateway_transaction_id' => $transactionId,
                'paid_at' => $paidAt,
            ]);

            $auditService->log(
                event: 'subscription_payment.recorded',
                auditableType: SubscriptionPayment::class,
                auditableId: $payment->id,
                newValues: $payment->getAttributes(),
                tenant: $tenant,
            );

            return $lockedCheckout->refresh();
        });
    }

    private function minorUnits(string $amount, string $currency): int
    {
        $exponent = self::CURRENCY_EXPONENTS[$currency];
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        if ($exponent === 0) {
            if (isset($fraction) && (int) $fraction !== 0) {
                throw ValidationException::withMessages([
                    'payment' => "The selected plan price must be a whole amount for {$currency}.",
                ]);
            }

            return (int) $whole;
        }

        return ((int) $whole * (10 ** $exponent))
            + (int) str_pad(substr($fraction, 0, $exponent), $exponent, '0');
    }

    private function periodEnd(Carbon $periodStartsAt): Carbon
    {
        $periodEndsAt = $periodStartsAt->copy()->addMonthNoOverflow();

        if ($periodStartsAt->isLastOfMonth()) {
            return $periodEndsAt->endOfMonth();
        }

        if ($periodStartsAt->day === $periodEndsAt->day) {
            return $periodEndsAt->subDay()->endOfDay();
        }

        return $periodEndsAt->endOfDay();
    }
}
