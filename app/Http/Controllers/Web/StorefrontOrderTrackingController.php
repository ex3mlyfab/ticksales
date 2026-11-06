<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StorefrontOrder;
use App\Models\Tenant;
use App\Support\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontOrderTrackingController extends Controller
{
    public function show(Request $request, Tenant $tenant, StorefrontOrder $storefrontOrder, AuditService $auditService): Response
    {
        abort_unless(
            $tenant->is_active && ($tenant->tenantSetting?->is_storefront_enabled ?? true),
            404,
        );
        abort_unless($storefrontOrder->tenant_id === $tenant->id, 404);

        $trackingToken = $request->session()->get('storefront_order_tracking_'.$storefrontOrder->id);
        $isCustomer = $request->user()?->id !== null
            && $request->user()?->id === $storefrontOrder->customer_user_id;
        $hasTrackingToken = is_string($trackingToken)
            && hash_equals($storefrontOrder->tracking_token_hash, hash('sha256', $trackingToken));

        abort_unless($isCustomer || $hasTrackingToken, 404);

        $auditService->log(
            'storefront.order.tracking_accessed',
            StorefrontOrder::class,
            $storefrontOrder->id,
            newValues: [
                'access_method' => $isCustomer ? 'customer_account' : 'tracking_token',
            ],
            tenant: $tenant,
        );

        $storefrontOrder->load(['items:id,storefront_order_id,name,quantity,unit_price,line_total', 'branch:id,name']);

        return Inertia::render('storefront/order', [
            'store' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'currency' => $tenant->currency,
            ],
            'order' => [
                'id' => $storefrontOrder->id,
                'reference_no' => $storefrontOrder->reference_no,
                'status' => $storefrontOrder->status,
                'payment_status' => $storefrontOrder->payment_status,
                'payment_method' => $storefrontOrder->payment_method,
                'fulfillment_method' => $storefrontOrder->fulfillment_method,
                'carrier_name' => $storefrontOrder->carrier_name,
                'tracking_number' => $storefrontOrder->tracking_number,
                'subtotal' => $storefrontOrder->subtotal,
                'tax_amount' => $storefrontOrder->tax_amount,
                'delivery_fee' => $storefrontOrder->delivery_fee,
                'total_amount' => $storefrontOrder->total_amount,
                'branch_name' => $storefrontOrder->branch->name,
                'items' => $storefrontOrder->items->map(fn ($item): array => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ])->all(),
                'payment_instructions' => $storefrontOrder->payment_method === 'bank_transfer'
                    && $storefrontOrder->payment_status === 'unpaid'
                    ? $tenant->tenantSetting?->bank_transfer_instructions
                    : null,
            ],
        ]);
    }
}
