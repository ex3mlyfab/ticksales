<?php

use App\Actions\Pos\CloseRegisterAction;
use App\Actions\Pos\OpenRegisterAction;
use App\Actions\Sales\CreateSaleAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
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

use function Pest\Laravel\actingAs;

test('open register action creates an open session', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $register = Register::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
    ]);
    $cashier = User::factory()->create();

    $session = OpenRegisterAction::execute([
        'tenant_id' => $tenant->id,
        'register_id' => $register->id,
        'branch_id' => $branch->id,
        'opened_by' => $cashier->getKey(),
        'opening_balance' => 500.00,
    ]);

    expect($session->status)->toBe('open')
        ->and($session->opening_balance)->toBe('500.0000')
        ->and($session->register_id)->toBe($register->id);
});

test('open register action rejects a second open session for the same register', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $register = Register::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
    ]);
    $cashier = User::factory()->create();

    OpenRegisterAction::execute([
        'tenant_id' => $tenant->id,
        'register_id' => $register->id,
        'branch_id' => $branch->id,
        'opened_by' => $cashier->getKey(),
        'opening_balance' => 500.00,
    ]);

    expect(fn () => OpenRegisterAction::execute([
        'tenant_id' => $tenant->id,
        'register_id' => $register->id,
        'branch_id' => $branch->id,
        'opened_by' => $cashier->getKey(),
        'opening_balance' => 500.00,
    ]))->toThrow(RuntimeException::class);
});

test('close register action computes variance between expected and actual balances', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $register = Register::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
    ]);
    $cashier = User::factory()->create();

    $session = OpenRegisterAction::execute([
        'tenant_id' => $tenant->id,
        'register_id' => $register->id,
        'branch_id' => $branch->id,
        'opened_by' => $cashier->getKey(),
        'opening_balance' => 500.00,
    ]);

    $closed = CloseRegisterAction::execute([
        'register_session_id' => $session->id,
        'closed_by' => $cashier->getKey(),
        'actual_balance' => 520.00,
    ]);

    expect($closed->status)->toBe('closed')
        ->and($closed->expected_balance)->toBe('500.0000')
        ->and($closed->variance)->toBe('20.0000')
        ->and($closed->closed_by)->toBe($cashier->getKey());
});

test('create sale action records sale, payments, decrements inventory and logs transactions', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Espresso',
        'sku' => 'ESP-001',
        'selling_price' => 12.00,
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
        'quantity' => 10,
    ]);

    $sale = CreateSaleAction::execute([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'sold_by' => $cashier->getKey(),
        'items' => [
            [
                'product_id' => $product->id,
                'name' => 'Espresso',
                'sku' => 'ESP-001',
                'quantity' => 3,
                'unit_price' => 12.00,
                'tax_rate' => 0,
            ],
        ],
        'payments' => [
            [
                'payment_method_id' => $method->id,
                'amount' => 36.00,
            ],
        ],
        'discount_amount' => 0,
    ]);

    expect($sale->total_amount)->toBe('36.0000')
        ->and($sale->status)->toBe('completed')
        ->and($sale->subtotal)->toBe('36.0000')
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('7.0000')
        ->and(SaleItem::query()->where('sale_id', $sale->id)->count())->toBe(1)
        ->and(SalePayment::query()->where('sale_id', $sale->id)->count())->toBe(1)
        ->and(InventoryTransaction::query()->where('reference_id', $sale->id)->count())->toBe(1);
});

test('create sale action is idempotent for the same idempotency key', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'selling_price' => 10.00,
    ]);
    $method = PaymentMethod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Card',
        'type' => 'card',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    Inventory::query()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $data = [
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'sold_by' => $cashier->getKey(),
        'idempotency_key' => 'abc-123-sale-key',
        'items' => [
            [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => 1,
                'unit_price' => 10.00,
                'tax_rate' => 0,
            ],
        ],
        'payments' => [
            [
                'payment_method_id' => $method->id,
                'amount' => 10.00,
            ],
        ],
    ];

    $first = CreateSaleAction::execute($data);
    $second = CreateSaleAction::execute($data);

    expect($second->id)->toBe($first->id)
        ->and(Sale::query()->count())->toBe(1)
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('9.0000');
});

test('payment methods can be created and updated via web controller', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/payment-methods', [
            'tenant_id' => $tenant->id,
            'name' => 'M-Pesa',
            'type' => 'mobile_money',
            'is_active' => true,
            'sort_order' => 3,
        ])
        ->assertRedirect(route('payment-methods.index'));

    $method = PaymentMethod::query()->where('name', 'M-Pesa')->first();

    expect($method)->not->toBeNull()
        ->and($method->tenant_id)->toBe($tenant->id);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/payment-methods/'.$method->id, [
            'tenant_id' => $tenant->id,
            'name' => 'M-Pesa (Updated)',
            'type' => 'mobile_money',
            'is_active' => false,
            'sort_order' => 4,
        ])
        ->assertRedirect(route('payment-methods.index'));

    expect($method->fresh()->name)->toBe('M-Pesa (Updated)')
        ->and($method->fresh()->is_active)->toBeFalse();
});

test('registers can be created and updated via web controller', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/registers', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Counter 1',
            'is_active' => true,
        ])
        ->assertRedirect(route('registers.index'));

    $register = Register::query()->where('name', 'Counter 1')->first();

    expect($register)->not->toBeNull()
        ->and($register->tenant_id)->toBe($tenant->id)
        ->and($register->branch_id)->toBe($branch->id);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->put('/registers/'.$register->id, [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Counter 2',
            'is_active' => false,
        ])
        ->assertRedirect(route('registers.index'));

    expect($register->fresh()->name)->toBe('Counter 2')
        ->and($register->fresh()->is_active)->toBeFalse();
});

test('register sessions can be opened via web controller', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $register = Register::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
    ]);

    attachUserToTenant($user, $tenant);

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/register-sessions', [
            'register_id' => $register->id,
            'branch_id' => $branch->id,
            'opening_balance' => 100.00,
        ])
        ->assertRedirect(route('pos.index'));

    $session = RegisterSession::query()->where('register_id', $register->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->status)->toBe('open')
        ->and($session->opened_by)->toBe($user->getKey());
});

test('offline sale sync is idempotent via the web store endpoint', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $product = Product::factory()->create([
        'tenant_id' => $tenant->id,
        'selling_price' => 12.00,
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

    attachUserToTenant($user, $tenant);

    $payload = [
        'branch_id' => $branch->id,
        'items' => [
            [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => 1,
                'unit_price' => 12.00,
                'tax_rate' => 0,
            ],
        ],
        'payments' => [
            [
                'payment_method_id' => $method->id,
                'amount' => 12.00,
            ],
        ],
        'idempotency_key' => (string) Str::uuid(),
        'synced_from_offline' => true,
        'offline_created_at' => now()->subHour()->toIso8601String(),
    ];

    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/pos/sales', $payload)
        ->assertRedirect();

    // Re-sync the same queued sale with the same key.
    actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post('/pos/sales', $payload)
        ->assertRedirect();

    expect(Sale::query()->count())->toBe(1)
        ->and(Inventory::query()->where('product_id', $product->id)->where('branch_id', $branch->id)->value('quantity'))->toBe('4.0000')
        ->and(Sale::query()->first()->synced_from_offline)->toBeTrue();
});
