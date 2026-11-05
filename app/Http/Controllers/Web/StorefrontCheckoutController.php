<?php

namespace App\Http\Controllers\Web;

use App\Actions\Storefront\CreateStorefrontOrderAction;
use App\Actions\Storefront\StorefrontCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorefrontCheckoutRequest;
use App\Models\Branch;
use App\Models\Tenant;
use App\Support\Payments\PaystackPaymentException;
use App\Support\Payments\PaystackStorefrontCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontCheckoutController extends Controller
{
    public function show(Request $request, Tenant $tenant, StorefrontCart $cart): Response
    {
        $this->assertStorefrontEnabled($tenant);

        $idempotencyKey = 'storefront_checkout_idempotency_'.$tenant->id;
        $trackingTokenKey = 'storefront_checkout_tracking_'.$tenant->id;

        if (
            $request->session()->get('storefront_checkout_completed_'.$tenant->id, false)
            || ! $request->session()->has($idempotencyKey)
            || ! $request->session()->has($trackingTokenKey)
        ) {
            $request->session()->put($idempotencyKey, Str::uuid()->toString());
            $request->session()->put($trackingTokenKey, Str::random(64));
            $request->session()->forget('storefront_checkout_completed_'.$tenant->id);
        }

        $mainBranches = Branch::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_main', true)
            ->where('is_active', true)
            ->get();

        return Inertia::render('storefront/checkout', [
            'store' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'currency' => $tenant->currency,
            ],
            'items' => $cart->present($request, $tenant),
            'idempotencyKey' => $request->session()->get($idempotencyKey),
            'mainBranchReady' => $mainBranches->count() === 1,
            'deliveryFee' => $mainBranches->count() === 1
                ? $mainBranches->first()->local_delivery_fee
                : '0.0000',
            'bankTransferEnabled' => filled($tenant->tenantSetting?->bank_transfer_instructions),
            'bankTransferInstructions' => $tenant->tenantSetting?->bank_transfer_instructions,
            'pickupEnabled' => $tenant->tenantSetting?->storefront_pickup_enabled ?? true,
            'deliveryEnabled' => $tenant->tenantSetting?->storefront_delivery_enabled ?? true,
            'paystackEnabled' => $tenant->tenantSetting?->paystack_enabled ?? false,
        ]);
    }

    public function store(
        StorefrontCheckoutRequest $request,
        Tenant $tenant,
        StorefrontCart $cart,
        CreateStorefrontOrderAction $createOrder,
        PaystackStorefrontCheckout $paystack,
    ): RedirectResponse {
        $this->assertStorefrontEnabled($tenant);

        $data = $request->validated();
        if (
            ($data['fulfillment_method'] === 'pickup' && ! ($tenant->tenantSetting?->storefront_pickup_enabled ?? true))
            || ($data['fulfillment_method'] === 'local_delivery' && ! ($tenant->tenantSetting?->storefront_delivery_enabled ?? true))
        ) {
            throw ValidationException::withMessages([
                'fulfillment_method' => 'This fulfillment method is not currently available.',
            ]);
        }
        $expectedIdempotencyKey = $request->session()->get('storefront_checkout_idempotency_'.$tenant->id);
        $trackingToken = $request->session()->get('storefront_checkout_tracking_'.$tenant->id);

        if (
            ! is_string($expectedIdempotencyKey)
            || ! hash_equals($expectedIdempotencyKey, $data['idempotency_key'])
            || ! is_string($trackingToken)
        ) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Your checkout session expired. Please review your order and try again.',
            ]);
        }

        $order = $createOrder->execute(
            tenant: $tenant,
            customerData: $data,
            cart: $cart->lines($request, $tenant),
            customer: $request->user(),
            trackingToken: $trackingToken,
        );

        $request->session()->put('storefront_order_tracking_'.$order->id, $trackingToken);
        $request->session()->forget($cart->sessionKey($tenant));

        if ($order->payment_method === 'paystack') {
            try {
                $authorizationUrl = $paystack->initialize(
                    $order,
                    $tenant->tenantSetting()->firstOrFail(),
                );
            } catch (PaystackPaymentException $exception) {
                throw ValidationException::withMessages([
                    'payment_method' => $exception->getMessage(),
                ]);
            }

            return redirect()->away($authorizationUrl);
        }

        $request->session()->put('storefront_checkout_completed_'.$tenant->id, true);

        return to_route('storefront.orders.show', [$tenant->slug, $order->id]);
    }

    private function assertStorefrontEnabled(Tenant $tenant): void
    {
        abort_unless(
            $tenant->is_active && ($tenant->tenantSetting?->is_storefront_enabled ?? true),
            404,
        );
    }
}
