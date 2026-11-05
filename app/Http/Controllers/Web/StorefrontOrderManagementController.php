<?php

namespace App\Http\Controllers\Web;

use App\Actions\Storefront\EraseStorefrontCustomerDataAction;
use App\Actions\Storefront\ManageStorefrontOrderAction;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StorefrontOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontOrderManagementController extends Controller
{
    public function index(Tenant $tenant): Response
    {
        $orders = StorefrontOrder::query()
            ->where('tenant_id', $tenant->id)
            ->with([
                'branch:id,name',
                'items:id,storefront_order_id,name,quantity,unit_price,line_total',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('storefront/orders/index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'currency' => $tenant->currency,
            ],
            'orders' => $orders->map(fn (StorefrontOrder $order): array => [
                'id' => $order->id,
                'reference_no' => $order->reference_no,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'payment_reference' => $order->payment_reference,
                'fulfillment_method' => $order->fulfillment_method,
                'carrier_name' => $order->carrier_name,
                'tracking_number' => $order->tracking_number,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'customer_email' => $order->customer_email,
                'delivery_address' => $order->delivery_address,
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'delivery_fee' => $order->delivery_fee,
                'total_amount' => $order->total_amount,
                'branch' => [
                    'id' => $order->branch->id,
                    'name' => $order->branch->name,
                ],
                'items' => $order->items->map(fn ($item): array => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ])->all(),
                'created_at' => $order->created_at?->toIso8601String(),
                'personal_data_erased_at' => $order->personal_data_erased_at?->toIso8601String(),
            ])->all(),
            'branches' => Branch::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'local_delivery_fee']),
        ]);
    }

    public function accept(
        Request $request,
        Tenant $tenant,
        StorefrontOrder $storefrontOrder,
        ManageStorefrontOrderAction $manageOrder,
    ): RedirectResponse {
        $manageOrder->accept($tenant, $storefrontOrder, $this->user($request));

        return back();
    }

    public function markReady(
        Request $request,
        Tenant $tenant,
        StorefrontOrder $storefrontOrder,
        ManageStorefrontOrderAction $manageOrder,
    ): RedirectResponse {
        $manageOrder->markReady($tenant, $storefrontOrder, $this->user($request));

        return back();
    }

    public function complete(
        Request $request,
        Tenant $tenant,
        StorefrontOrder $storefrontOrder,
        ManageStorefrontOrderAction $manageOrder,
    ): RedirectResponse {
        $manageOrder->complete($tenant, $storefrontOrder, $this->user($request));

        return back();
    }

    public function confirmPayment(
        Request $request,
        Tenant $tenant,
        StorefrontOrder $storefrontOrder,
        ManageStorefrontOrderAction $manageOrder,
    ): RedirectResponse {
        $data = $request->validate([
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $manageOrder->confirmPayment(
            $tenant,
            $storefrontOrder,
            $this->user($request),
            $data['payment_reference'] ?? null,
        );

        return back();
    }

    public function cancel(
        Request $request,
        Tenant $tenant,
        StorefrontOrder $storefrontOrder,
        ManageStorefrontOrderAction $manageOrder,
    ): RedirectResponse {
        $data = $request->validate([
            'cancellation_note' => ['required', 'string', 'max:2000'],
            'refund_handled_externally' => ['nullable', 'boolean'],
            'refund_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $manageOrder->cancel(
            $tenant,
            $storefrontOrder,
            $this->user($request),
            $data['cancellation_note'],
            (bool) ($data['refund_handled_externally'] ?? false),
            $data['refund_note'] ?? null,
        );

        return back();
    }

    public function reassignBranch(
        Request $request,
        Tenant $tenant,
        StorefrontOrder $storefrontOrder,
        ManageStorefrontOrderAction $manageOrder,
    ): RedirectResponse {
        $data = $request->validate([
            'branch_id' => [
                'required',
                'ulid',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
        ]);

        $branch = Branch::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($data['branch_id'])
            ->firstOrFail();

        $manageOrder->reassignBranch($tenant, $storefrontOrder, $branch, $this->user($request));

        return back();
    }

    public function updateDeliveryTracking(
        Request $request,
        Tenant $tenant,
        StorefrontOrder $storefrontOrder,
        ManageStorefrontOrderAction $manageOrder,
    ): RedirectResponse {
        $data = $request->validate([
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
        ]);

        $manageOrder->updateDeliveryTracking(
            $tenant,
            $storefrontOrder,
            $this->user($request),
            $data['carrier_name'] ?? null,
            $data['tracking_number'] ?? null,
        );

        return back();
    }

    public function eraseCustomerData(
        Request $request,
        Tenant $tenant,
        StorefrontOrder $storefrontOrder,
        EraseStorefrontCustomerDataAction $eraseAction,
    ): RedirectResponse {
        $eraseAction->erase($tenant, $storefrontOrder, $this->user($request));

        return back()->with('success', 'Customer personal data erased.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 404);

        return $user;
    }
}
