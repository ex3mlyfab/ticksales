<?php

namespace App\Actions\Storefront;

use App\Actions\Inventory\AdjustInventoryAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderEvent;
use App\Models\StorefrontStockReservation;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\StorefrontOrderStatusNotification;
use App\Support\AuditService;
use App\Support\StorefrontAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageStorefrontOrderAction
{
    public function __construct(
        protected AuditService $auditService,
        protected StorefrontAnalytics $storefrontAnalytics,
    ) {}

    public function accept(Tenant $tenant, StorefrontOrder $order, User $actor): StorefrontOrder
    {
        return $this->changeStatus($tenant, $order, $actor, 'pending', 'accepted');
    }

    public function markReady(Tenant $tenant, StorefrontOrder $order, User $actor): StorefrontOrder
    {
        return $this->changeStatus($tenant, $order, $actor, 'accepted', 'ready');
    }

    public function complete(Tenant $tenant, StorefrontOrder $order, User $actor): StorefrontOrder
    {
        $completedOrder = DB::transaction(function () use ($tenant, $order, $actor): StorefrontOrder {
            $lockedOrder = $this->lockOrder($tenant, $order);

            if ($lockedOrder->status !== 'ready') {
                throw ValidationException::withMessages([
                    'status' => 'Only ready orders can be completed.',
                ]);
            }

            if ($lockedOrder->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'payment_status' => 'Confirm payment before completing the order.',
                ]);
            }

            $this->recordStatusChange($tenant, $lockedOrder, $actor, 'completed');

            return $lockedOrder->refresh();
        }, 3);
        StorefrontOrderStatusNotification::sendForOrder($completedOrder, 'status_changed');

        return $completedOrder;
    }

    public function confirmPayment(
        Tenant $tenant,
        StorefrontOrder $order,
        User $actor,
        ?string $paymentReference,
    ): StorefrontOrder {
        $wasPaid = $order->payment_status === 'paid';
        $confirmedOrder = DB::transaction(function () use ($tenant, $order, $actor, $paymentReference): StorefrontOrder {
            $lockedOrder = $this->lockOrder($tenant, $order);

            if (in_array($lockedOrder->status, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Payment cannot be confirmed for this order.',
                ]);
            }

            if ($lockedOrder->payment_status === 'paid') {
                return $lockedOrder;
            }

            if (
                $lockedOrder->payment_method === 'cash'
                && ! in_array($lockedOrder->status, ['ready'], true)
            ) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Cash can only be recorded when the order is ready for handoff.',
                ]);
            }

            if ($lockedOrder->payment_method === 'bank_transfer' && blank($paymentReference)) {
                throw ValidationException::withMessages([
                    'payment_reference' => 'Enter the bank transfer reference before confirming payment.',
                ]);
            }

            $items = $lockedOrder->items()
                ->with('stockReservation')
                ->orderBy('id')
                ->get();

            foreach ($items as $item) {
                $reservation = $item->stockReservation;

                if ($reservation === null || $reservation->status !== 'active') {
                    continue;
                }

                $inventory = Inventory::query()
                    ->whereKey($reservation->inventory_id)
                    ->where('tenant_id', $tenant->id)
                    ->where('branch_id', $lockedOrder->branch_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                AdjustInventoryAction::handle(
                    tenantId: $tenant->id,
                    branchId: $lockedOrder->branch_id,
                    productId: $inventory->product_id,
                    variantId: $inventory->variant_id,
                    quantityChanged: -((float) $reservation->quantity),
                    referenceType: StorefrontOrder::class,
                    referenceId: $lockedOrder->id,
                    type: 'storefront_sale',
                    note: 'Online order payment confirmed '.$lockedOrder->reference_no,
                    createdBy: $actor->id,
                    requireSufficientStock: true,
                    reservationIdToConsume: $reservation->id,
                );

                $reservation->update(['status' => 'consumed']);
            }

            $previousPaymentStatus = $lockedOrder->payment_status;
            $previousPaymentReference = $lockedOrder->payment_reference;

            $lockedOrder->update([
                'payment_status' => 'paid',
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => $actor->id,
                'payment_reference' => $paymentReference,
            ]);

            StorefrontOrderEvent::query()->create([
                'storefront_order_id' => $lockedOrder->id,
                'tenant_id' => $tenant->id,
                'actor_id' => $actor->id,
                'event' => 'payment_confirmed',
                'metadata' => [
                    'payment_method' => $lockedOrder->payment_method,
                    'payment_reference' => $paymentReference,
                ],
            ]);

            $this->auditService->log(
                'storefront.order.payment_confirmed',
                StorefrontOrder::class,
                $lockedOrder->id,
                [
                    'payment_status' => $previousPaymentStatus,
                    'payment_reference' => $previousPaymentReference,
                ],
                [
                    'payment_status' => 'paid',
                    'payment_reference' => $paymentReference,
                ],
                tenant: $tenant,
            );
            StorefrontOrderStatusNotification::sendForOrder($lockedOrder, 'payment_confirmed');

            return $lockedOrder->refresh();
        }, 3);

        if (! $wasPaid) {
            $this->storefrontAnalytics->record(
                request: request(),
                tenant: $tenant,
                event: 'order_paid',
            );
        }

        return $confirmedOrder;
    }

    public function cancel(
        Tenant $tenant,
        StorefrontOrder $order,
        User $actor,
        string $note,
        bool $refundHandledExternally,
        ?string $refundNote,
    ): StorefrontOrder {
        return DB::transaction(function () use ($tenant, $order, $actor, $note, $refundHandledExternally, $refundNote): StorefrontOrder {
            $lockedOrder = $this->lockOrder($tenant, $order);

            if ($lockedOrder->status === 'completed') {
                throw ValidationException::withMessages([
                    'status' => 'Completed orders cannot be cancelled here. Use the sale-return process.',
                ]);
            }

            if ($lockedOrder->status === 'cancelled') {
                return $lockedOrder;
            }

            if ($lockedOrder->payment_status === 'paid' && ! $refundHandledExternally) {
                throw ValidationException::withMessages([
                    'refund_handled_externally' => 'Confirm that the external refund has been handled before cancelling this paid order.',
                ]);
            }

            if ($lockedOrder->payment_status === 'paid' && blank($refundNote)) {
                throw ValidationException::withMessages([
                    'refund_note' => 'Record how the external refund was handled.',
                ]);
            }

            $reservations = StorefrontStockReservation::query()
                ->whereHas('orderItem', fn ($query) => $query->where('storefront_order_id', $lockedOrder->id))
                ->whereIn('status', ['active', 'consumed'])
                ->with('inventory')
                ->orderBy('inventory_id')
                ->get();

            foreach ($reservations as $reservation) {
                if ($reservation->status === 'consumed') {
                    $inventory = $reservation->inventory;

                    AdjustInventoryAction::handle(
                        tenantId: $tenant->id,
                        branchId: $inventory->branch_id,
                        productId: $inventory->product_id,
                        variantId: $inventory->variant_id,
                        quantityChanged: $reservation->quantity,
                        referenceType: StorefrontOrder::class,
                        referenceId: $lockedOrder->id,
                        type: 'storefront_cancellation',
                        note: 'Stock restored for cancelled online order '.$lockedOrder->reference_no,
                        createdBy: $actor->id,
                    );
                }

                $reservation->update(['status' => 'released']);
            }

            $fromStatus = $lockedOrder->status;
            $previousPaymentStatus = $lockedOrder->payment_status;

            $lockedOrder->update([
                'status' => 'cancelled',
                'payment_status' => $lockedOrder->payment_status === 'paid' ? 'refunded' : 'unpaid',
                'cancellation_note' => $note,
                'refund_handled_externally' => $lockedOrder->payment_status === 'paid',
                'refund_note' => $lockedOrder->payment_status === 'paid' ? $refundNote : null,
            ]);

            StorefrontOrderEvent::query()->create([
                'storefront_order_id' => $lockedOrder->id,
                'tenant_id' => $tenant->id,
                'actor_id' => $actor->id,
                'event' => 'order_cancelled',
                'from_status' => $fromStatus,
                'to_status' => 'cancelled',
                'metadata' => [
                    'refund_handled_externally' => $lockedOrder->payment_status === 'refunded',
                    'note' => $note,
                    'refund_note' => $refundNote,
                ],
            ]);

            $this->auditService->log(
                'storefront.order.cancelled',
                StorefrontOrder::class,
                $lockedOrder->id,
                [
                    'status' => $fromStatus,
                    'payment_status' => $previousPaymentStatus,
                    'cancellation_note' => $note,
                ],
                [
                    'status' => 'cancelled',
                    'payment_status' => $lockedOrder->payment_status,
                    'cancellation_note' => $note,
                ],
                tenant: $tenant,
            );

            if ($previousPaymentStatus === 'paid' && $lockedOrder->payment_status === 'refunded') {
                $this->auditService->log(
                    'storefront.order.refund_recorded',
                    StorefrontOrder::class,
                    $lockedOrder->id,
                    [
                        'payment_status' => $previousPaymentStatus,
                        'refund_handled_externally' => false,
                        'refund_note' => null,
                    ],
                    [
                        'payment_status' => 'refunded',
                        'refund_handled_externally' => true,
                        'refund_note' => $refundNote,
                    ],
                    tenant: $tenant,
                );
            }
            StorefrontOrderStatusNotification::sendForOrder($lockedOrder, 'order_cancelled');

            return $lockedOrder->refresh();
        }, 3);
    }

    public function reassignBranch(
        Tenant $tenant,
        StorefrontOrder $order,
        Branch $branch,
        User $actor,
    ): StorefrontOrder {
        return DB::transaction(function () use ($tenant, $order, $branch, $actor): StorefrontOrder {
            $lockedOrder = $this->lockOrder($tenant, $order);

            $destinationBranch = Branch::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($branch->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_status !== 'unpaid' || in_array($lockedOrder->status, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Only unpaid, open orders can be reassigned.',
                ]);
            }

            if ($lockedOrder->branch_id === $destinationBranch->id) {
                return $lockedOrder;
            }

            $items = $lockedOrder->items()
                ->with('stockReservation')
                ->orderBy('product_id')
                ->orderBy('variant_id')
                ->get();
            $inventoryIds = [];
            $destinationInventories = [];

            foreach ($items as $item) {
                if ($item->stockReservation === null || $item->stockReservation->status !== 'active') {
                    continue;
                }

                $inventory = Inventory::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('branch_id', $destinationBranch->id)
                    ->where('product_id', $item->product_id)
                    ->where('variant_id', $item->variant_id)
                    ->first();

                if ($inventory === null) {
                    throw ValidationException::withMessages([
                        'branch_id' => "The destination branch does not have stock for {$item->name}.",
                    ]);
                }

                $destinationInventories[$item->id] = $inventory;
                $inventoryIds[] = $inventory->id;
                $inventoryIds[] = $item->stockReservation->inventory_id;
            }

            $lockedInventories = Inventory::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('id', array_unique($inventoryIds))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $reservation = $item->stockReservation;

                if ($reservation === null || $reservation->status !== 'active') {
                    continue;
                }

                $destinationInventory = $lockedInventories->get($destinationInventories[$item->id]->id);
                $reservedQuantity = (float) StorefrontStockReservation::active()
                    ->where('inventory_id', $destinationInventory->id)
                    ->where('id', '!=', $reservation->id)
                    ->lockForUpdate()
                    ->get()
                    ->sum('quantity');

                if ((float) $destinationInventory->quantity - $reservedQuantity < (float) $reservation->quantity) {
                    throw ValidationException::withMessages([
                        'branch_id' => "The destination branch does not have enough unreserved stock for {$item->name}.",
                    ]);
                }
            }

            foreach ($items as $item) {
                $reservation = $item->stockReservation;

                if ($reservation !== null && $reservation->status === 'active') {
                    $reservation->update([
                        'inventory_id' => $destinationInventories[$item->id]->id,
                    ]);
                }
            }

            $fromBranchId = $lockedOrder->branch_id;
            $previousDeliveryFee = (float) $lockedOrder->delivery_fee;
            $newDeliveryFee = $lockedOrder->fulfillment_method === 'local_delivery'
                ? (float) $destinationBranch->local_delivery_fee
                : 0.0;
            $lockedOrder->update([
                'branch_id' => $destinationBranch->id,
                'delivery_fee' => $newDeliveryFee,
                'total_amount' => (float) $lockedOrder->total_amount - $previousDeliveryFee + $newDeliveryFee,
            ]);

            StorefrontOrderEvent::query()->create([
                'storefront_order_id' => $lockedOrder->id,
                'tenant_id' => $tenant->id,
                'actor_id' => $actor->id,
                'event' => 'branch_reassigned',
                'metadata' => [
                    'from_branch_id' => $fromBranchId,
                    'to_branch_id' => $destinationBranch->id,
                ],
            ]);

            $this->auditService->log(
                'storefront.order.branch_reassigned',
                StorefrontOrder::class,
                $lockedOrder->id,
                [
                    'branch_id' => $fromBranchId,
                    'delivery_fee' => $previousDeliveryFee,
                ],
                [
                    'branch_id' => $destinationBranch->id,
                    'delivery_fee' => $newDeliveryFee,
                ],
                tenant: $tenant,
            );
            StorefrontOrderStatusNotification::sendForOrder($lockedOrder, 'delivery_tracking_updated');

            return $lockedOrder->refresh();
        }, 3);
    }

    public function updateDeliveryTracking(
        Tenant $tenant,
        StorefrontOrder $order,
        User $actor,
        ?string $carrierName,
        ?string $trackingNumber,
    ): StorefrontOrder {
        return DB::transaction(function () use ($tenant, $order, $actor, $carrierName, $trackingNumber): StorefrontOrder {
            $lockedOrder = $this->lockOrder($tenant, $order);

            if ($lockedOrder->fulfillment_method !== 'local_delivery') {
                throw ValidationException::withMessages([
                    'tracking_number' => 'Delivery tracking is only available for local delivery orders.',
                ]);
            }

            if (in_array($lockedOrder->status, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'tracking_number' => 'Delivery tracking cannot be changed for a terminal order.',
                ]);
            }

            $previousCarrier = $lockedOrder->carrier_name;
            $previousTracking = $lockedOrder->tracking_number;
            $lockedOrder->update([
                'carrier_name' => blank($carrierName) ? null : $carrierName,
                'tracking_number' => blank($trackingNumber) ? null : $trackingNumber,
            ]);

            StorefrontOrderEvent::query()->create([
                'storefront_order_id' => $lockedOrder->id,
                'tenant_id' => $tenant->id,
                'actor_id' => $actor->id,
                'event' => 'delivery_tracking_updated',
                'metadata' => [
                    'carrier_name' => $lockedOrder->carrier_name,
                    'tracking_number' => $lockedOrder->tracking_number,
                ],
            ]);

            $this->auditService->log(
                'storefront.order.delivery_tracking_updated',
                StorefrontOrder::class,
                $lockedOrder->id,
                [
                    'carrier_name' => $previousCarrier,
                    'tracking_number' => $previousTracking,
                ],
                [
                    'carrier_name' => $lockedOrder->carrier_name,
                    'tracking_number' => $lockedOrder->tracking_number,
                ],
                tenant: $tenant,
            );
            StorefrontOrderStatusNotification::sendForOrder($order, 'status_changed');

            return $lockedOrder->refresh();
        }, 3);
    }

    private function changeStatus(
        Tenant $tenant,
        StorefrontOrder $order,
        User $actor,
        string $expectedStatus,
        string $newStatus,
    ): StorefrontOrder {
        $changedOrder = DB::transaction(function () use ($tenant, $order, $actor, $expectedStatus, $newStatus): StorefrontOrder {
            $lockedOrder = $this->lockOrder($tenant, $order);

            if ($lockedOrder->status !== $expectedStatus) {
                throw ValidationException::withMessages([
                    'status' => 'This order can no longer be updated in that way.',
                ]);
            }

            $this->recordStatusChange($tenant, $lockedOrder, $actor, $newStatus);

            return $lockedOrder->refresh();
        }, 3);
        StorefrontOrderStatusNotification::sendForOrder($changedOrder, 'status_changed');

        return $changedOrder;
    }

    private function recordStatusChange(Tenant $tenant, StorefrontOrder $order, User $actor, string $newStatus): void
    {
        $fromStatus = $order->status;
        $order->update(['status' => $newStatus]);

        StorefrontOrderEvent::query()->create([
            'storefront_order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'actor_id' => $actor->id,
            'event' => 'status_changed',
            'from_status' => $fromStatus,
            'to_status' => $newStatus,
        ]);

        $this->auditService->log(
            'storefront.order.status_changed',
            StorefrontOrder::class,
            $order->id,
            ['status' => $fromStatus],
            ['status' => $newStatus],
            tenant: $tenant,
        );
    }

    private function lockOrder(Tenant $tenant, StorefrontOrder $order): StorefrontOrder
    {
        return StorefrontOrder::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($order->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
