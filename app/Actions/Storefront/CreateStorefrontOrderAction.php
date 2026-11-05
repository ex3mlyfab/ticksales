<?php

namespace App\Actions\Storefront;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderEvent;
use App\Models\StorefrontOrderItem;
use App\Models\StorefrontStockReservation;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateStorefrontOrderAction
{
    public function __construct(private StorefrontPricing $pricing) {}

    /**
     * @param  array<string, mixed>  $customerData
     * @param  array<int|string, array<string, mixed>>  $cart
     */
    public function execute(
        Tenant $tenant,
        array $customerData,
        array $cart,
        ?User $customer,
        string $trackingToken,
    ): StorefrontOrder {
        return DB::transaction(function () use ($tenant, $customerData, $cart, $customer, $trackingToken): StorefrontOrder {
            $lockedTenant = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();

            $existingOrder = StorefrontOrder::query()
                ->where('tenant_id', $lockedTenant->id)
                ->where('idempotency_key', $customerData['idempotency_key'])
                ->first();

            if ($existingOrder !== null) {
                return $existingOrder->load('items', 'branch');
            }

            abort_unless(
                $lockedTenant->is_active
                    && ($lockedTenant->tenantSetting?->is_storefront_enabled ?? true),
                404,
            );

            $settings = TenantSetting::query()->where('tenant_id', $lockedTenant->id)->first();
            $mainBranches = Branch::query()
                ->where('tenant_id', $lockedTenant->id)
                ->where('is_main', true)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($mainBranches->count() !== 1) {
                throw ValidationException::withMessages([
                    'checkout' => 'This store is not ready to accept orders. Please contact the store.',
                ]);
            }

            $branch = $mainBranches->first();

            if ($customerData['payment_method'] === 'bank_transfer' && blank($settings?->bank_transfer_instructions)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Bank transfer is unavailable until the store adds transfer instructions.',
                ]);
            }
            if ($customerData['payment_method'] === 'paystack'
                && (! $settings?->paystack_enabled || blank($settings->paystack_secret))) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Paystack is unavailable until the store configures it.',
                ]);
            }

            $normalizedCart = $this->normalizeCart($cart);
            if ($normalizedCart === []) {
                throw ValidationException::withMessages([
                    'items' => 'Your cart is empty.',
                ]);
            }

            ksort($normalizedCart);

            $preparedItems = [];
            $subtotal = 0.0;
            $taxAmount = 0.0;

            foreach ($normalizedCart as $cartLine) {
                $product = Product::query()
                    ->where('tenant_id', $lockedTenant->id)
                    ->whereKey($cartLine['product_id'])
                    ->where('is_active', true)
                    ->where('is_storefront_published', true)
                    ->lockForUpdate()
                    ->first();

                if ($product === null) {
                    throw ValidationException::withMessages([
                        'items' => 'A product in your cart is no longer available.',
                    ]);
                }

                if (! $product->isStorefrontListable()) {
                    throw ValidationException::withMessages([
                        'items' => 'A product in your cart is no longer available.',
                    ]);
                }

                $variant = $this->resolveVariant($lockedTenant, $product, $cartLine['variant_id']);
                $quantity = $cartLine['quantity'];
                $pricing = $this->pricing->calculate($lockedTenant, $settings, $product, $variant, $quantity);

                $inventory = null;
                if ($product->track_inventory) {
                    $inventory = Inventory::query()
                        ->where('tenant_id', $lockedTenant->id)
                        ->where('branch_id', $branch->id)
                        ->where('product_id', $product->id)
                        ->where('variant_id', $variant?->id)
                        ->lockForUpdate()
                        ->first();

                    $reservedQuantity = $inventory === null
                        ? 0.0
                        : (float) StorefrontStockReservation::active()
                            ->where('inventory_id', $inventory->id)
                            ->lockForUpdate()
                            ->get()
                            ->sum('quantity');
                    $availableQuantity = (float) ($inventory?->quantity ?? 0) - $reservedQuantity;

                    if ($availableQuantity < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => "There is not enough stock for {$product->name}.",
                        ]);
                    }
                }

                $subtotal += $pricing['line_subtotal'];
                $taxAmount += $pricing['tax_amount'];
                $preparedItems[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'inventory' => $inventory,
                    'quantity' => $quantity,
                    ...$pricing,
                    'name' => $variant === null ? $product->name : $product->name.' - '.$variant->name,
                    'sku' => $variant?->sku ?? $product->sku,
                ];
            }

            $deliveryFee = $customerData['fulfillment_method'] === 'local_delivery'
                ? (float) $branch->local_delivery_fee
                : 0.0;
            $subtotal = round($subtotal, 4);
            $taxAmount = round($taxAmount, 4);
            $totalAmount = round(
                array_sum(array_column($preparedItems, 'line_total')) + $deliveryFee,
                4,
            );

            $order = StorefrontOrder::query()->create([
                'tenant_id' => $lockedTenant->id,
                'branch_id' => $branch->id,
                'customer_user_id' => $customer?->id,
                'reference_no' => 'WEB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'idempotency_key' => $customerData['idempotency_key'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $customerData['payment_method'],
                'fulfillment_method' => $customerData['fulfillment_method'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'customer_name' => $customerData['customer_name'],
                'customer_phone' => $customerData['customer_phone'],
                'customer_email' => $customerData['customer_email'] ?? null,
                'delivery_address' => $customerData['fulfillment_method'] === 'local_delivery'
                    ? $customerData['delivery_address']
                    : null,
                'tracking_token_hash' => hash('sha256', $trackingToken),
            ]);

            foreach ($preparedItems as $preparedItem) {
                $orderItem = StorefrontOrderItem::query()->create([
                    'storefront_order_id' => $order->id,
                    'product_id' => $preparedItem['product']->id,
                    'variant_id' => $preparedItem['variant']?->id,
                    'name' => $preparedItem['name'],
                    'sku' => $preparedItem['sku'],
                    'quantity' => $preparedItem['quantity'],
                    'unit_price' => $preparedItem['unit_price'],
                    'tax_rate' => $preparedItem['tax_rate'],
                    'tax_amount' => $preparedItem['tax_amount'],
                    'line_subtotal' => $preparedItem['line_subtotal'],
                    'line_total' => $preparedItem['line_total'],
                    'tax_inclusive' => $preparedItem['tax_inclusive'],
                ]);

                if ($preparedItem['inventory'] !== null) {
                    StorefrontStockReservation::query()->create([
                        'storefront_order_item_id' => $orderItem->id,
                        'inventory_id' => $preparedItem['inventory']->id,
                        'quantity' => $preparedItem['quantity'],
                        'status' => 'active',
                    ]);
                }
            }

            StorefrontOrderEvent::query()->create([
                'storefront_order_id' => $order->id,
                'tenant_id' => $lockedTenant->id,
                'event' => 'order_placed',
                'to_status' => 'pending',
            ]);

            return $order->load('items', 'branch');
        }, 3);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $cart
     * @return array<string, array{product_id: string, variant_id: string|null, quantity: float}>
     */
    private function normalizeCart(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $line) {
            if (
                ! is_array($line)
                || ! is_string($line['product_id'] ?? null)
                || ! is_numeric($line['quantity'] ?? null)
                || ! is_finite((float) $line['quantity'])
                || (float) $line['quantity'] <= 0
            ) {
                throw ValidationException::withMessages([
                    'items' => 'Your cart contains an invalid item. Please update your cart and try again.',
                ]);
            }

            $productId = $line['product_id'];
            $variantId = is_string($line['variant_id'] ?? null) ? $line['variant_id'] : null;
            $key = $productId.':'.($variantId ?? '');
            $quantity = (float) $line['quantity'];

            $normalized[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => round(($normalized[$key]['quantity'] ?? 0) + $quantity, 4),
            ];
        }

        return $normalized;
    }

    private function resolveVariant(Tenant $tenant, Product $product, ?string $variantId): ?ProductVariant
    {
        if (! $product->has_variants) {
            if ($variantId !== null) {
                throw ValidationException::withMessages([
                    'items' => 'One of the selected product options is no longer available.',
                ]);
            }

            return null;
        }

        if ($variantId === null) {
            throw ValidationException::withMessages([
                'items' => "Choose an option for {$product->name}.",
            ]);
        }

        $variant = ProductVariant::query()
            ->where('tenant_id', $tenant->id)
            ->where('product_id', $product->id)
            ->whereKey($variantId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if ($variant === null) {
            throw ValidationException::withMessages([
                'items' => 'One of the selected product options is no longer available.',
            ]);
        }

        if (
            ! filled($variant->name)
            || $variant->selling_price === null
            || (float) $variant->selling_price <= 0
        ) {
            throw ValidationException::withMessages([
                'items' => 'One of the selected product options is no longer available.',
            ]);
        }

        return $variant;
    }
}
