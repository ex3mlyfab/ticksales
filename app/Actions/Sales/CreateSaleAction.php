<?php

namespace App\Actions\Sales;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                'reference_no' => 'SALE-'.now()->format('Y-m-d').'-'.str_pad((string) Sale::where('tenant_id', $tenantId)->count(), 6, '0', STR_PAD_LEFT),
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

                // Decrement inventory
                $inventory = Inventory::query()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'branch_id' => $branchId,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                    ],
                    ['quantity' => 0]
                );

                $inventory->lockForUpdate();
                $inventory->decrement('quantity', $item['quantity']);
                $inventory->save();

                InventoryTransaction::query()->create([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => -$item['quantity'],
                    'type' => 'sale',
                    'reference_id' => $sale->id,
                    'reference_type' => Sale::class,
                    'note' => 'Stock sold on '.$sale->reference_no,
                ]);
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
}
