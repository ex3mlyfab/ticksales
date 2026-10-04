<?php

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Tenant;
use App\Models\User;

test('sale returns can record refunded items and refund method', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);
    $sale = Sale::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'sold_by' => $cashier->getKey(),
    ]);
    $method = PaymentMethod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Card',
        'type' => 'card',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $return = SaleReturn::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'sale_id' => $sale->id,
        'reference_no' => 'RET-1001',
        'reason' => 'Customer requested return',
        'refund_amount' => '19.4400',
        'refund_method_id' => $method->id,
        'status' => 'completed',
        'returned_by' => $cashier->getKey(),
        'returned_at' => now(),
    ]);

    $returnItem = SaleReturnItem::query()->create([
        'sale_return_id' => $return->id,
        'product_id' => $product->id,
        'quantity' => '1.0000',
        'unit_price' => '9.7200',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.7200',
        'line_total' => '9.7200',
    ]);

    expect($return->sale_id)->toBe($sale->id)
        ->and($return->refund_method_id)->toBe($method->id)
        ->and($return->status)->toBe('completed')
        ->and($returnItem->sale_return_id)->toBe($return->id)
        ->and($returnItem->line_total)->toBe('9.7200');
});
