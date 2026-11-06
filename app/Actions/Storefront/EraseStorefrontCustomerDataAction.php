<?php

namespace App\Actions\Storefront;

use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EraseStorefrontCustomerDataAction
{
    public function __construct(private readonly AuditService $auditService) {}

    public function erase(Tenant $tenant, StorefrontOrder $order, ?User $actor = null): StorefrontOrder
    {
        return DB::transaction(function () use ($tenant, $order, $actor): StorefrontOrder {
            $lockedOrder = StorefrontOrder::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedOrder->status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Customer data can only be erased after an order is completed or cancelled.',
                ]);
            }

            if ($lockedOrder->personal_data_erased_at !== null) {
                return $lockedOrder;
            }

            $lockedOrder->update([
                'customer_user_id' => null,
                'customer_name' => '[deleted]',
                'customer_phone' => '[deleted]',
                'customer_email' => null,
                'delivery_address' => null,
                'cancellation_note' => null,
                'refund_note' => null,
                'tracking_token_hash' => hash('sha256', Str::random(64)),
                'personal_data_erased_at' => now(),
            ]);

            foreach ($lockedOrder->events()->get() as $event) {
                $metadata = $event->metadata ?? [];
                foreach ([
                    'note',
                    'refund_note',
                    'cancellation_note',
                    'customer_name',
                    'customer_phone',
                    'customer_email',
                    'delivery_address',
                ] as $key) {
                    unset($metadata[$key]);
                }
                $event->update(['metadata' => $metadata]);
            }

            StorefrontOrderEvent::query()->create([
                'storefront_order_id' => $lockedOrder->id,
                'tenant_id' => $tenant->id,
                'actor_id' => $actor?->id,
                'event' => 'customer_data_erased',
                'from_status' => $lockedOrder->status,
                'to_status' => $lockedOrder->status,
                'metadata' => [
                    'source' => $actor === null ? 'retention_policy' : 'staff_request',
                ],
            ]);

            $this->auditService->log(
                'storefront.order.customer_data_erased',
                StorefrontOrder::class,
                $lockedOrder->id,
                oldValues: [
                    'personal_data_erased_at' => null,
                ],
                newValues: [
                    'personal_data_erased_at' => $lockedOrder->personal_data_erased_at,
                    'source' => $actor === null ? 'retention_policy' : 'staff_request',
                ],
                tenant: $tenant,
            );

            return $lockedOrder->refresh();
        }, 3);
    }
}
