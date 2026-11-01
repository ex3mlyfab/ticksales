<?php

use App\Actions\Purchasing\ReceivePurchaseAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('receive purchase action records purchase, increments inventory and logs transactions', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Coffee Beans',
        'sku' => 'COF-001',
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $purchase = ReceivePurchaseAction::execute([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'purchase_date' => now()->toDateString(),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 20,
                'unit_cost' => 3.50,
                'tax_rate' => 0,
                'discount_amount' => 0,
            ],
        ],
    ]);

    expect($purchase->status)->toBe('completed')
        ->and($purchase->total)->toBe('70.0000')
        ->and(PurchaseItem::query()->where('purchase_id', $purchase->id)->count())->toBe(1)
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('25.0000')
        ->and(InventoryTransaction::query()->where('reference_id', $purchase->id)->count())->toBe(1);
});

test('purchase order can be created via web controller with calculated totals', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/purchase-orders', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 4.00,
                    'tax_rate' => 10,
                    'discount_amount' => 5.00,
                ],
            ],
        ])
        ->assertRedirect(route('purchase-orders.index'));

    $order = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe('draft')
        ->and(PurchaseOrderItem::query()->where('purchase_order_id', $order->id)->count())->toBe(1);
});

test('purchase order posting receives stock and updates order status', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Milk',
        'sku' => 'MILK-001',
    ]);

    $order = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PO-TEST-001',
        'status' => 'draft',
        'order_date' => now()->toDateString(),
        'subtotal' => 0,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 0,
    ]);

    PurchaseOrderItem::query()->create([
        'purchase_order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 12,
        'unit_cost' => 2.00,
        'tax_rate' => 0,
        'discount_amount' => 0,
        'line_total' => 24.00,
    ]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/purchase-orders/'.$order->id.'/post')
        ->assertRedirect(route('purchase-orders.index'));

    expect($order->fresh()->status)->toBe('posted')
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('12.0000')
        ->and(Purchase::query()->where('purchase_order_id', $order->id)->count())->toBe(1);
});

test('purchase return posting decreases inventory', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Return Item',
        'sku' => 'RET-001',
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 20,
    ]);

    $purchaseReturn = PurchaseReturn::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PR-TEST-001',
        'reason' => 'Damaged goods',
        'return_date' => now()->toDateString(),
        'subtotal' => 0,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 0,
        'status' => 'draft',
    ]);

    PurchaseReturnItem::query()->create([
        'purchase_return_id' => $purchaseReturn->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_cost' => 2.00,
        'tax_rate' => 0,
        'discount_amount' => 0,
        'line_total' => 10.00,
    ]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/purchase-returns/'.$purchaseReturn->id.'/post')
        ->assertRedirect(route('purchase-returns.index'));

    expect($purchaseReturn->fresh()->status)->toBe('posted')
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('15.0000');
});

test('purchase order store derives tenant from session when tenant_id is omitted', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/purchase-orders', [
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_cost' => 2.00,
                ],
            ],
        ])
        ->assertRedirect(route('purchase-orders.index'));

    expect(PurchaseOrder::query()->where('supplier_id', $supplier->id)->exists())->toBeTrue();
});

test('purchase order pages render for an authenticated tenant user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Sugar']);

    $order = PurchaseOrder::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PO-PAGE-001',
        'status' => 'draft',
        'order_date' => now()->toDateString(),
        'subtotal' => 10,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 10,
    ]);

    PurchaseOrderItem::query()->create([
        'purchase_order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_cost' => 2.00,
        'tax_rate' => 0,
        'discount_amount' => 0,
        'line_total' => 10.00,
    ]);

    attachUserToTenant($user, $tenant);

    actingAs($user)->withSession(['active_tenant_id' => $tenant->id]);

    get('/purchase-orders')->assertOk();
    get('/purchase-orders/create')->assertOk();
    get('/purchase-orders/'.$order->id)->assertOk();
    get('/purchase-orders/'.$order->id.'/edit')->assertOk();
});

test('purchase pages render for an authenticated tenant user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $purchase = Purchase::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PUR-PAGE-001',
        'purchase_date' => now()->toDateString(),
        'subtotal' => 5,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 5,
        'paid_amount' => 0,
        'status' => 'completed',
    ]);

    attachUserToTenant($user, $tenant);

    actingAs($user)->withSession(['active_tenant_id' => $tenant->id]);

    get('/purchases')->assertOk();
    get('/purchases/create')->assertOk();
    get('/purchases/'.$purchase->id)->assertOk();
});

test('purchase return pages render for an authenticated tenant user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $purchaseReturn = PurchaseReturn::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'supplier_id' => $supplier->id,
        'reference_no' => 'PR-PAGE-001',
        'reason' => 'Damaged goods',
        'return_date' => now()->toDateString(),
        'subtotal' => 4,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total' => 4,
        'status' => 'draft',
    ]);

    PurchaseReturnItem::query()->create([
        'purchase_return_id' => $purchaseReturn->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_cost' => 2.00,
        'tax_rate' => 0,
        'discount_amount' => 0,
        'line_total' => 4.00,
    ]);

    attachUserToTenant($user, $tenant);

    actingAs($user)->withSession(['active_tenant_id' => $tenant->id]);

    get('/purchase-returns')->assertOk();
    get('/purchase-returns/create')->assertOk();
    get('/purchase-returns/'.$purchaseReturn->id)->assertOk();
    get('/purchase-returns/'.$purchaseReturn->id.'/edit')->assertOk();
});
