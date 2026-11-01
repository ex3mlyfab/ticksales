<?php

use App\Actions\Sales\RefundSaleAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

test('refund sale action restores inventory and marks the sale as refunded', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Desk Lamp',
        'sku' => 'LAMP-001',
    ]);
    $method = PaymentMethod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Cash',
        'type' => 'cash',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $sale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'reference_no' => 'SALE-REFUND-001',
        'idempotency_key' => '4d87d3ce-1c80-4a3a-8d8f-6f255610d2db',
        'status' => 'completed',
        'subtotal' => '20.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '1.6000',
        'total_amount' => '21.6000',
        'paid_amount' => '21.6000',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'note' => 'Full refund test',
        'sold_by' => $cashier->getKey(),
        'sold_at' => now(),
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'quantity' => '2.0000',
        'unit_price' => '10.8000',
        'cost_price' => '7.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.8000',
        'tax_rate' => '0.0800',
        'total_amount' => '21.6000',
    ]);

    $saleReturn = (new RefundSaleAction)->execute([
        'sale_id' => $sale->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'refund_method_id' => $method->id,
        'returned_by' => $cashier->getKey(),
        'reason' => 'Customer requested a full return',
    ]);

    expect($saleReturn->sale_id)->toBe($sale->id)
        ->and($saleReturn->refund_amount)->toBe('21.6000')
        ->and($saleReturn->status)->toBe('completed')
        ->and($sale->fresh()->status)->toBe('refunded')
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('7.0000');
});

test('refunding a multi-line sale does not lazy load per line', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $method = PaymentMethod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Cash',
        'type' => 'cash',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    // A sale with many lines and a prior return exercises the previously
    // N+1 path (`$item->sale->returns` inside quantityToRefund): the old code
    // issued a query per line. Count the queries and assert they stay bounded.
    $sale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'reference_no' => 'SALE-NPLUS-001',
        'idempotency_key' => (string) Str::uuid(),
        'status' => 'completed',
        'subtotal' => '50.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.0000',
        'total_amount' => '50.0000',
        'paid_amount' => '50.0000',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'sold_by' => $cashier->getKey(),
        'sold_at' => now(),
    ]);

    for ($i = 1; $i <= 6; $i++) {
        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => Product::factory()->create(['tenant_id' => $tenant->id])->id,
            'name' => "Line $i",
            'sku' => "SKU-$i",
            'quantity' => '1.0000',
            'unit_price' => '8.3300',
            'cost_price' => '5.0000',
            'discount_amount' => '0.0000',
            'tax_amount' => '0.0000',
            'tax_rate' => '0.0000',
            'total_amount' => '8.3300',
        ]);
    }

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    // A partial return (with an explicit items list) is what drives the old
    // per-line lazy load; a full refund short-circuits before it.
    $items = $sale->items->map(fn (SaleItem $item) => [
        'product_id' => $item->product_id,
        'variant_id' => $item->variant_id,
        'quantity' => 1,
    ])->all();

    (new RefundSaleAction)->execute([
        'sale_id' => $sale->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'refund_method_id' => $method->id,
        'returned_by' => $cashier->getKey(),
        'reason' => 'Multi-line return',
        'items' => $items,
    ]);

    DB::flushQueryLog();

    // The refund touches the sale + lines (1), prior returns (1), the return
    // (1), its six items, six inventory restores and six transactions, plus a
    // little framework overhead: 37 queries for 6 lines. The old per-line lazy
    // load (`$item->sale->returns` inside the loop) added ~3 queries per line,
    // pushing the same sale to ~55. Assert under 45 so a regression is caught
    // without coupling to the exact framework overhead.
    expect($queries)->toBeLessThan(45);
});
