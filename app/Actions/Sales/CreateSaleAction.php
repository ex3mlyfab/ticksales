<?php

namespace App\Actions\Sales;

use App\Actions\Inventory\AdjustInventoryAction;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateSaleAction
{
    /**
     * Create a sale transactionally, updating inventory and recording payments.
     */
    public static function execute(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];
            $branchId = $data['branch_id'];
            $registerSessionId = $data['register_session_id'] ?? null;
            $customerId = $data['customer_id'] ?? null;
            $soldBy = $data['sold_by'];
            $items = $data['items'];
            $payments = $data['payments'] ?? [];
            $note = $data['note'] ?? null;
            $discountAmount = $data['discount_amount'] ?? 0;
            $idempotencyKey = $data['idempotency_key'] ?? Str::uuid()->toString();

            // Idempotency check: prevent duplicate sale if same key
            $existingSale = Sale::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($existingSale) {
                return $existingSale;
            }

            // Every referenced record must belong to the active tenant, so a
            // caller can never attach another tenant's product, customer,
            // register session, branch, or payment method to this sale.
            self::assertOwnedByTenant($tenantId, $branchId, $registerSessionId, $customerId, $items, $payments);

            // Validate open register session
            if ($registerSessionId) {
                $session = RegisterSession::query()->findOrFail($registerSessionId);
                if ($session->status !== 'open') {
                    throw new \RuntimeException('Register session is not open.');
                }
            }

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
                $taxAmount += $item['quantity'] * $item['unit_price'] * (($item['tax_rate'] ?? 0) / 100);
            }

            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            $paidAmount = 0;
            foreach ($payments as $payment) {
                $paidAmount += $payment['amount'];
            }

            $changeAmount = max(0, $paidAmount - $totalAmount);
            $outstandingAmount = max(0, $totalAmount - $paidAmount);

            $sale = Sale::query()->create([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'register_session_id' => $registerSessionId,
                'customer_id' => $customerId,
                'reference_no' => 'SALE-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4)),
                'idempotency_key' => $idempotencyKey,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'outstanding_amount' => $outstandingAmount,
                'note' => $note,
                'sold_by' => $soldBy,
                'sold_at' => now(),
                'synced_from_offline' => $data['synced_from_offline'] ?? false,
                'offline_created_at' => $data['offline_created_at'] ?? null,
            ]);

            foreach ($items as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTax = $itemSubtotal * (($item['tax_rate'] ?? 0) / 100);
                $itemDiscount = $item['discount_amount'] ?? 0;
                $itemTotal = $itemSubtotal + $itemTax - $itemDiscount;

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'name' => $item['name'] ?? '',
                    'sku' => $item['sku'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $item['cost_price'] ?? null,
                    'discount_amount' => $itemDiscount,
                    'tax_amount' => $itemTax,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'total_amount' => $itemTotal,
                ]);

                // Decrement inventory and record the stock ledger entry.
                AdjustInventoryAction::handle(
                    tenantId: $tenantId,
                    branchId: $branchId,
                    productId: $item['product_id'],
                    variantId: $item['variant_id'] ?? null,
                    quantityChanged: -$item['quantity'],
                    referenceType: Sale::class,
                    referenceId: $sale->id,
                    type: 'sale',
                    note: 'Stock sold on '.$sale->reference_no,
                    createdBy: $soldBy,
                );
            }

            foreach ($payments as $payment) {
                SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'payment_method_id' => $payment['payment_method_id'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                ]);
            }

            return $sale;
        });
    }

    /**
     * Ensure every referenced record belongs to the given tenant.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $payments
     */
    private static function assertOwnedByTenant(
        string $tenantId,
        ?string $branchId,
        ?string $registerSessionId,
        ?string $customerId,
        array $items,
        array $payments,
    ): void {
        if ($branchId !== null && ! Branch::query()->where('tenant_id', $tenantId)->whereKey($branchId)->exists()) {
            throw ValidationException::withMessages(['branch_id' => 'The selected branch is invalid.']);
        }

        if ($registerSessionId !== null && ! RegisterSession::query()->where('tenant_id', $tenantId)->whereKey($registerSessionId)->exists()) {
            throw ValidationException::withMessages(['register_session_id' => 'The selected register session is invalid.']);
        }

        if ($customerId !== null && ! Customer::query()->where('tenant_id', $tenantId)->whereKey($customerId)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'The selected customer is invalid.']);
        }

        foreach ($items as $item) {
            if (! Product::query()->where('tenant_id', $tenantId)->whereKey($item['product_id'])->exists()) {
                throw ValidationException::withMessages(['items' => 'One of the selected products is invalid.']);
            }
        }

        foreach ($payments as $payment) {
            if (! PaymentMethod::query()->where('tenant_id', $tenantId)->whereKey($payment['payment_method_id'])->exists()) {
                throw ValidationException::withMessages(['payments' => 'One of the selected payment methods is invalid.']);
            }
        }
    }
}
