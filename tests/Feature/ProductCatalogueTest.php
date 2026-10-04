<?php

use App\Actions\Inventory\AdjustInventoryAction;
use App\Actions\Inventory\TransferStockAction;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Support\Str;

test('product catalogue entities can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $category = ProductCategory::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Beverages',
        'slug' => 'beverages',
        'description' => 'Cold drinks and juices',
        'is_active' => true,
    ]);

    $brand = ProductBrand::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Northwind',
    ]);

    $unit = ProductUnit::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Bottle',
        'abbreviation' => 'btl',
    ]);

    expect($category->tenant_id)->toBe($tenant->id)
        ->and($category->slug)->toBe('beverages')
        ->and($brand->name)->toBe('Northwind')
        ->and($unit->abbreviation)->toBe('btl');
});

test('branches and inventory records can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $branch = Branch::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Downtown Store',
        'code' => 'DTW',
        'address' => '123 Main St',
        'phone' => '555-0101',
        'email' => 'downtown@example.com',
        'is_main' => true,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $inventory = Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 45,
    ]);

    expect($branch->tenant_id)->toBe($tenant->id)
        ->and($branch->code)->toBe('DTW')
        ->and($inventory->branch_id)->toBe($branch->id)
        ->and($inventory->product_id)->toBe($product->id)
        ->and((float) $inventory->quantity)->toBe(45.0);
});

test('inventory transactions can record stock movement history', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $transaction = InventoryTransaction::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'reference_type' => 'Purchase',
        'reference_id' => (string) Str::ulid(),
        'type' => 'Purchase',
        'quantity' => '12.5000',
        'unit_cost' => '4.2500',
        'note' => 'Initial restock',
    ]);

    expect($transaction->tenant_id)->toBe($tenant->id)
        ->and($transaction->branch_id)->toBe($branch->id)
        ->and($transaction->product_id)->toBe($product->id)
        ->and($transaction->quantity)->toBe('12.5000')
        ->and($transaction->type)->toBe('Purchase');
});

test('stock adjustments and stock transfers can record inventory changes', function () {
    $tenant = Tenant::factory()->create();
    $fromBranch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $toBranch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    $adjustment = StockAdjustment::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fromBranch->id,
        'reference_no' => 'ADJ-1001',
        'reason' => 'Cycle count correction',
        'status' => 'completed',
        'adjusted_by' => null,
    ]);

    $adjustmentItem = StockAdjustmentItem::query()->create([
        'adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity_before' => '50.0000',
        'quantity_after' => '62.0000',
        'quantity_adjusted' => '12.0000',
        'unit_cost' => '3.2500',
    ]);

    $transfer = StockTransfer::query()->create([
        'tenant_id' => $tenant->id,
        'from_branch_id' => $fromBranch->id,
        'to_branch_id' => $toBranch->id,
        'reference_no' => 'TRF-1001',
        'status' => 'pending',
        'note' => 'Warehouse transfer',
    ]);

    $transferItem = StockTransferItem::query()->create([
        'transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'quantity' => '8.0000',
        'unit_cost' => '3.2500',
    ]);

    expect($adjustment->tenant_id)->toBe($tenant->id)
        ->and($adjustmentItem->adjustment_id)->toBe($adjustment->id)
        ->and($transfer->from_branch_id)->toBe($fromBranch->id)
        ->and($transferItem->transfer_id)->toBe($transfer->id)
        ->and($transferItem->quantity)->toBe('8.0000');
});

test('customer groups can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $group = CustomerGroup::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'VIP Retailers',
        'description' => 'Preferred customers with wholesale pricing',
        'discount_rate' => '12.5000',
        'is_active' => true,
    ]);

    expect($group->tenant_id)->toBe($tenant->id)
        ->and($group->name)->toBe('VIP Retailers')
        ->and($group->discount_rate)->toBe('12.5000');
});

test('customers and suppliers can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();
    $group = CustomerGroup::factory()->create(['tenant_id' => $tenant->id]);

    $customer = Customer::query()->create([
        'tenant_id' => $tenant->id,
        'customer_group_id' => $group->id,
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'phone' => '555-0102',
        'address' => '42 Market Street',
        'credit_limit' => '2500.0000',
        'outstanding_balance' => '125.5000',
        'loyalty_points' => 120,
        'is_active' => true,
    ]);

    $supplier = Supplier::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Prime Wholesale',
        'email' => 'supplier@example.com',
        'phone' => '555-0202',
        'address' => '9 Supply Road',
        'tax_number' => 'TX-88442',
        'outstanding_balance' => '650.2500',
        'is_active' => true,
    ]);

    expect($customer->tenant_id)->toBe($tenant->id)
        ->and($customer->customer_group_id)->toBe($group->id)
        ->and($customer->credit_limit)->toBe('2500.0000')
        ->and($supplier->tenant_id)->toBe($tenant->id)
        ->and($supplier->tax_number)->toBe('TX-88442')
        ->and($supplier->outstanding_balance)->toBe('650.2500');
});

test('inventory adjustment and stock transfer actions update branch stock and ledger', function () {
    $tenant = Tenant::factory()->create();
    $branchA = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $branchB = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branchA->id,
        'product_id' => $product->id,
        'quantity' => 25,
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branchB->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $adjustAction = new AdjustInventoryAction;
    $adjustment = $adjustAction->execute([
        'tenant_id' => $tenant->id,
        'branch_id' => $branchA->id,
        'product_id' => $product->id,
        'quantity_delta' => 12,
        'reference_type' => 'CycleCount',
        'reference_id' => (string) Str::ulid(),
        'type' => 'Adjustment',
        'note' => 'Cycle count correction',
    ]);

    $transferAction = new TransferStockAction;
    $transfer = $transferAction->execute([
        'tenant_id' => $tenant->id,
        'from_branch_id' => $branchA->id,
        'to_branch_id' => $branchB->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'reference_no' => 'TRF-INT-1',
        'note' => 'Branch replenishment',
    ]);

    expect($adjustment->status)->toBe('completed')
        ->and($adjustment->items()->count())->toBe(1)
        ->and($transfer->status)->toBe('completed')
        ->and($transfer->items()->count())->toBe(1)
        ->and(Inventory::query()->where('branch_id', $branchA->id)->where('product_id', $product->id)->value('quantity'))->toBe('32.0000')
        ->and(Inventory::query()->where('branch_id', $branchB->id)->where('product_id', $product->id)->value('quantity'))->toBe('15.0000');
});
