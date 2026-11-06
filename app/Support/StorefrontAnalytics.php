<?php

namespace App\Support;

use App\Models\StorefrontAnalyticsEvent;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StorefrontAnalytics
{
    public const ALLOWED_EVENTS = [
        'product_viewed',
        'cart_item_added',
        'cart_item_removed',
        'checkout_started',
        'order_paid',
    ];

    public function record(
        Request $request,
        Tenant $tenant,
        string $event,
        ?string $productId = null,
        ?string $variantId = null,
    ): void {
        if (! in_array($event, self::ALLOWED_EVENTS, true)) {
            return;
        }

        if (! ($tenant->tenantSetting?->is_storefront_analytics_enabled ?? false)) {
            return;
        }

        $sessionKey = 'storefront_analytics_session_'.$tenant->id;
        $sessionId = $request->session()->get($sessionKey);

        if (! is_string($sessionId) || $sessionId === '') {
            $sessionId = Str::random(64);
            $request->session()->put($sessionKey, $sessionId);
        }

        StorefrontAnalyticsEvent::query()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'event' => $event,
            'session_hash' => hash('sha256', $sessionId),
        ]);
    }
}
