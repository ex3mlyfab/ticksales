<?php

use App\Actions\Sales\RefundSaleAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('sale return routes redirect guests to login', function () {
    get('/sale-returns')->assertRedirect('/login');
    get('/sale-returns/create')->assertRedirect('/login');
});

test('sale returns index renders for an authenticated tenant user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->get('/sale-returns')
        ->assertOk();
});

function makeSaleWithItems(Tenant $tenant, Branch $branch, User $cashier, array $products): Sale
{
    $sale = Sale::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'reference_no' => 'SALE-TEST-'.fake()->unique()->numerify('####'),
        'idempotency_key' => fake()->uuid(),
        'status' => 'completed',
        'subtotal' => '0.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.0000',
        'total_amount' => '0.0000',
        'paid_amount' => '0.0000',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'sold_by' => $cashier->getKey(),
        'sold_at' => now(),
    ]);

    foreach ($products as $product) {
        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product['id'],
            'name' => $product['name'],
            'sku' => $product['sku'],
            'quantity' => $product['quantity'],
            'unit_price' => $product['unit_price'],
            'cost_price' => $product['cost_price'] ?? '0.0000',
            'discount_amount' => '0.0000',
            'tax_amount' => '0.0000',
            'tax_rate' => '0.0000',
            'total_amount' => $product['line_total'],
        ]);
    }

    return $sale;
}

test('refund action restores stock for a partial return and marks sale partially refunded', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Partial Lamp',
        'sku' => 'PART-001',
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 8,
    ]);

    $sale = makeSaleWithItems($tenant, $branch, $cashier, [[
        'id' => $product->id,
        'name' => 'Partial Lamp',
        'sku' => 'PART-001',
        'quantity' => '4.0000',
        'unit_price' => '10.0000',
        'line_total' => '40.0000',
    ]]);

    $saleReturn = (new RefundSaleAction)->execute([
        'sale_id' => $sale->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'returned_by' => $cashier->getKey(),
        'reason' => 'Partial return',
        'items' => [
            [
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => '2.0000',
            ],
        ],
    ]);

    expect($saleReturn->refund_amount)->toBe('20.0000')
        ->and($sale->fresh()->status)->toBe('partially_refunded')
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('10.0000')
        ->and(SaleReturn::query()->count())->toBe(1);
});

test('refund action caps the returned quantity at the remaining returnable amount', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Capped Item',
        'sku' => 'CAP-001',
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $sale = makeSaleWithItems($tenant, $branch, $cashier, [[
        'id' => $product->id,
        'name' => 'Capped Item',
        'sku' => 'CAP-001',
        'quantity' => '3.0000',
        'unit_price' => '8.0000',
        'line_total' => '24.0000',
    ]]);

    // First refund 1 unit.
    (new RefundSaleAction)->execute([
        'sale_id' => $sale->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'returned_by' => $cashier->getKey(),
        'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => '1.0000']],
    ]);

    // Attempt to over-refund (5 units when only 2 remain) — should cap at 2.
    $second = (new RefundSaleAction)->execute([
        'sale_id' => $sale->id,
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'returned_by' => $cashier->getKey(),
        'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => '5.0000']],
    ]);

    expect((float) $second->refund_amount)->toBe(16.0)
        ->and($sale->fresh()->status)->toBe('refunded')
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('8.0000');
});

test('sale return can be processed via web controller', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Web Return',
        'sku' => 'WEB-001',
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
        'quantity' => 6,
    ]);

    $sale = makeSaleWithItems($tenant, $branch, $user, [[
        'id' => $product->id,
        'name' => 'Web Return',
        'sku' => 'WEB-001',
        'quantity' => '3.0000',
        'unit_price' => '10.0000',
        'line_total' => '30.0000',
    ]]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/sale-returns', [
            'sale_id' => $sale->id,
            'refund_method_id' => $method->id,
            'reason' => 'Web refund',
            'items' => [
                ['product_id' => $product->id, 'variant_id' => null, 'quantity' => '1.0000'],
            ],
        ])
        ->assertRedirect();

    $saleReturn = SaleReturn::query()->first();

    expect($saleReturn)->not->toBeNull()
        ->and($saleReturn->sale_id)->toBe($sale->id)
        ->and($saleReturn->refund_method_id)->toBe($method->id)
        ->and((float) $saleReturn->refund_amount)->toBe(10.0)
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('7.0000')
        ->and(InventoryTransaction::query()->where('reference_id', $saleReturn->id)->count())->toBe(1);
});

test('sale return rejects a sale from another tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $otherTenant->id]);
    $product = Product::factory()->create(['tenant_id' => $otherTenant->id]);
    $sale = makeSaleWithItems($otherTenant, $branch, $user, [[
        'id' => $product->id,
        'name' => 'Other',
        'sku' => 'OTHER-001',
        'quantity' => '2.0000',
        'unit_price' => '5.0000',
        'line_total' => '10.0000',
    ]]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/sale-returns', [
            'sale_id' => $sale->id,
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => '1.0000']],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['sale_id']);

    expect(SaleReturn::query()->count())->toBe(0);
});

test('sale return requires at least one item', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);
    $sale = makeSaleWithItems($tenant, $branch, $user, [[
        'id' => $product->id,
        'name' => 'No Item',
        'sku' => 'NOI-001',
        'quantity' => '1.0000',
        'unit_price' => '5.0000',
        'line_total' => '5.0000',
    ]]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/sale-returns', [
            'sale_id' => $sale->id,
            'items' => [],
        ])
        ->assertSessionHasErrors('items');
});
