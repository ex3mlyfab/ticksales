<?php

namespace App\Support\Payments;

use App\Actions\Inventory\AdjustInventoryAction;
use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderEvent;
use App\Models\StorefrontPaymentEvent;
use App\Models\TenantSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaystackStorefrontCheckout
{
    private const API_URL = 'https://api.paystack.co';

    private const CURRENCY_EXPONENTS = [
        'NGN' => 2, 'GHS' => 2, 'ZAR' => 2, 'USD' => 2, 'KES' => 2,
        'XOF' => 0, 'RWF' => 0, 'TZS' => 2, 'UGX' => 0, 'ZMW' => 2,
        'XAF' => 0, 'EGP' => 2,
    ];

    public function isEnabled(TenantSetting $settings): bool
    {
        return $settings->paystack_enabled
            && is_string($settings->paystack_secret)
            && $settings->paystack_secret !== '';
    }

    public function supportsCurrency(string $currency): bool
    {
        return isset(self::CURRENCY_EXPONENTS[strtoupper($currency)]);
    }

    public function initialize(StorefrontOrder $order, TenantSetting $settings): string
    {
        if (! $this->isEnabled($settings)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Paystack is not configured for this store.',
            ]);
        }

        $currency = strtoupper($order->tenant->currency);
        if (! $this->supportsCurrency($currency)) {
            throw ValidationException::withMessages([
                'payment_method' => "Paystack does not support {$order->tenant->currency}.",
            ]);
        }
        $email = $order->customer_email ?: $settings->storefront_email;
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'customer_email' => 'A valid email address is required for Paystack checkout.',
            ]);
        }

        $reference = 'TSO-'.Str::ulid();
        try {
            $response = Http::acceptJson()
                ->withToken($settings->paystack_secret)
                ->connectTimeout(5)
                ->timeout(20)
                ->post(self::API_URL.'/transaction/initialize', [
                    'email' => $email,
                    'amount' => $this->minorUnits((string) $order->total_amount, $currency),
                    'currency' => $currency,
                    'reference' => $reference,
                    'callback_url' => route('storefront.paystack.callback', [
                        'tenant' => $order->tenant->slug,
                    ]),
                    'metadata' => [
                        'storefront_order_id' => $order->id,
                        'tenant_id' => $order->tenant_id,
                        'reference_no' => $order->reference_no,
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw new PaystackPaymentException('Paystack could not be reached. Please try again.', previous: $exception);
        }

        $payload = $response->json();
        $authorizationUrl = data_get($payload, 'data.authorization_url');
        $providerReference = data_get($payload, 'data.reference');
        if (! $response->successful()
            || data_get($payload, 'status') !== true
            || ! is_string($authorizationUrl)
            || ! str_starts_with($authorizationUrl, 'https://')
            || $providerReference !== $reference) {
            throw new PaystackPaymentException('Paystack did not create a checkout session.');
        }

        $order->update([
            'payment_provider' => 'paystack',
            'provider_reference' => $reference,
        ]);

        return $authorizationUrl;
    }

    public function verify(StorefrontOrder $order, string $providerEventId = ''): StorefrontOrder
    {
        return DB::transaction(function () use ($order, $providerEventId): StorefrontOrder {
            $lockedOrder = StorefrontOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->payment_status === 'paid') {
                return $lockedOrder;
            }

            $settings = TenantSetting::query()->where('tenant_id', $lockedOrder->tenant_id)->firstOrFail();
            if (! $this->isEnabled($settings) || blank($lockedOrder->provider_reference)) {
                throw new PaystackPaymentException('Paystack verification is not configured for this order.');
            }

            try {
                $response = Http::acceptJson()
                    ->withToken($settings->paystack_secret)
                    ->connectTimeout(5)
                    ->timeout(20)
                    ->get(self::API_URL.'/transaction/verify/'.rawurlencode($lockedOrder->provider_reference));
            } catch (ConnectionException $exception) {
                throw new PaystackPaymentException('Paystack could not be reached to verify this payment.', previous: $exception);
            }

            $payload = $response->json();
            if (! $response->successful() || data_get($payload, 'status') !== true) {
                throw new PaystackPaymentException('Paystack could not verify this transaction.');
            }

            $transaction = data_get($payload, 'data');
            if (! is_array($transaction) || data_get($transaction, 'status') !== 'success') {
                return $lockedOrder->refresh();
            }

            $currency = strtoupper((string) $lockedOrder->tenant->currency);
            if (data_get($transaction, 'reference') !== $lockedOrder->provider_reference
                || strtoupper((string) data_get($transaction, 'currency')) !== $currency
                || (int) data_get($transaction, 'amount') !== $this->minorUnits((string) $lockedOrder->total_amount, $currency)) {
                throw new PaystackPaymentException('The verified transaction did not match this order.');
            }

            $eventId = $providerEventId !== ''
                ? $providerEventId
                : (string) data_get($transaction, 'id');

            if ($eventId === '' || StorefrontPaymentEvent::query()
                ->where('provider', 'paystack')
                ->where('provider_event_id', $eventId)
                ->exists()) {
                if ($eventId === '') {
                    throw new PaystackPaymentException('Paystack did not return a transaction event identifier.');
                }

                return $lockedOrder->refresh();
            }

            $items = $lockedOrder->items()->with('stockReservation')->orderBy('id')->get();
            foreach ($items as $item) {
                $reservation = $item->stockReservation;
                if ($reservation === null || $reservation->status !== 'active') {
                    continue;
                }
                $inventory = $reservation->inventory()->lockForUpdate()->firstOrFail();
                AdjustInventoryAction::handle(
                    tenantId: $lockedOrder->tenant_id,
                    branchId: $lockedOrder->branch_id,
                    productId: $inventory->product_id,
                    variantId: $inventory->variant_id,
                    quantityChanged: -((float) $reservation->quantity),
                    referenceType: StorefrontOrder::class,
                    referenceId: $lockedOrder->id,
                    type: 'storefront_sale',
                    note: 'Paystack online order payment verified '.$lockedOrder->reference_no,
                    requireSufficientStock: true,
                    reservationIdToConsume: $reservation->id,
                );
                $reservation->update(['status' => 'consumed']);
            }

            $lockedOrder->update([
                'payment_status' => 'paid',
                'payment_confirmed_at' => now(),
                'payment_reference' => $lockedOrder->provider_reference,
                'provider_event_id' => $eventId,
            ]);

            StorefrontPaymentEvent::query()->create([
                'tenant_id' => $lockedOrder->tenant_id,
                'storefront_order_id' => $lockedOrder->id,
                'provider' => 'paystack',
                'provider_event_id' => $eventId,
                'event_type' => 'charge.success',
                'payload' => [
                    'reference' => data_get($transaction, 'reference'),
                    'amount' => data_get($transaction, 'amount'),
                    'currency' => data_get($transaction, 'currency'),
                ],
            ]);
            StorefrontOrderEvent::query()->create([
                'storefront_order_id' => $lockedOrder->id,
                'tenant_id' => $lockedOrder->tenant_id,
                'event' => 'payment_confirmed',
                'metadata' => [
                    'payment_method' => 'paystack',
                    'payment_reference' => $lockedOrder->provider_reference,
                    'provider_event_id' => $eventId,
                ],
            ]);

            return $lockedOrder->refresh();
        }, 3);
    }

    private function minorUnits(string $amount, string $currency): int
    {
        return (int) round(((float) $amount) * (10 ** self::CURRENCY_EXPONENTS[$currency]));
    }
}
