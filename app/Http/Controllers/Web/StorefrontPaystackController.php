<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StorefrontOrder;
use App\Models\Tenant;
use App\Support\Payments\PaystackPaymentException;
use App\Support\Payments\PaystackStorefrontCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StorefrontPaystackController extends Controller
{
    public function callback(
        Tenant $tenant,
        Request $request,
        PaystackStorefrontCheckout $paystack,
    ): RedirectResponse {
        $reference = $request->string('reference')->toString()
            ?: $request->string('trxref')->toString();
        $order = StorefrontOrder::query()
            ->where('tenant_id', $tenant->id)
            ->where('provider_reference', $reference)
            ->first();

        abort_unless($order !== null, 404);

        try {
            $order = $paystack->verify($order);
        } catch (PaystackPaymentException $exception) {
            return to_route('storefront.orders.show', [$tenant->slug, $order->id])
                ->with('error', $exception->getMessage());
        }

        return to_route('storefront.orders.show', [$tenant->slug, $order->id])
            ->with($order->payment_status === 'paid' ? 'success' : 'error',
                $order->payment_status === 'paid'
                    ? 'Payment confirmed.'
                    : 'Paystack has not confirmed this payment yet.');
    }

    public function webhook(
        Request $request,
        PaystackStorefrontCheckout $paystack,
    ): JsonResponse {
        $payload = $request->json()->all();
        $reference = data_get($payload, 'data.reference');
        abort_unless(is_string($reference) && $reference !== '', 400);

        $order = StorefrontOrder::query()
            ->where('provider_reference', $reference)
            ->first();
        abort_unless($order !== null, 404);

        $settings = $order->tenant->tenantSetting()->first();
        abort_unless($settings !== null && $settings->paystack_secret !== '', 503);

        $signature = $request->header('x-paystack-signature');
        abort_unless(is_string($signature), 400);
        abort_unless(hash_equals(
            hash_hmac('sha512', $request->getContent(), $settings->paystack_secret),
            $signature,
        ), 400);

        if (data_get($payload, 'event') !== 'charge.success') {
            return response()->json(['received' => true]);
        }

        $eventId = (string) data_get($payload, 'data.id');
        abort_unless($eventId !== '', 400);

        try {
            $paystack->verify($order, $eventId);
        } catch (PaystackPaymentException) {
            return response()->json(['received' => true]);
        }

        return response()->json(['received' => true]);
    }
}
