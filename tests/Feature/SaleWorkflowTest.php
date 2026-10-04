<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

test('sales can contain items and payment allocations', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);
    $method = PaymentMethod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Cash',
        'type' => 'cash',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $register = Register::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Counter 1',
        'is_active' => true,
    ]);
    $session = RegisterSession::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'register_id' => $register->id,
        'opened_by' => $cashier->getKey(),
        'opening_balance' => '100.0000',
        'status' => 'open',
        'opened_at' => now(),
    ]);

    $sale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'register_session_id' => $session->id,
        'customer_id' => $customer->id,
        'reference_no' => 'SALE-INV-1001',
        'idempotency_key' => (string) Str::uuid(),
        'status' => 'completed',
        'subtotal' => '18.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '1.4400',
        'total_amount' => '19.4400',
        'paid_amount' => '19.4400',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'note' => 'Test sale',
        'sold_by' => $cashier->getKey(),
        'sold_at' => now(),
    ]);

    $lineItem = SaleItem::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'quantity' => '2.0000',
        'unit_price' => '9.0000',
        'cost_price' => '4.5000',
        'discount_amount' => '0.0000',
        'tax_amount' => '1.4400',
        'tax_rate' => '0.0800',
        'total_amount' => '19.4400',
    ]);

    $payment = SalePayment::query()->create([
        'sale_id' => $sale->id,
        'payment_method_id' => $method->id,
        'amount' => '19.4400',
    ]);

    expect($sale->reference_no)->toBe('SALE-INV-1001')
        ->and($sale->status)->toBe('completed')
        ->and($sale->total_amount)->toBe('19.4400')
        ->and($lineItem->sale_id)->toBe($sale->id)
        ->and($lineItem->quantity)->toBe('2.0000')
        ->and($payment->payment_method_id)->toBe($method->id)
        ->and($payment->amount)->toBe('19.4400');
});
