<?php

use App\Actions\Inventory\AdjustInventoryAction;
use App\Actions\Storefront\EraseStorefrontCustomerDataAction;
use App\Actions\Storefront\ManageStorefrontOrderAction;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderEvent;
use App\Models\StorefrontPaymentEvent;
use App\Models\StorefrontStockReservation;
use App\Models\TaxRate;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Payments\PaystackStorefrontCheckout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function createStorefrontCheckoutFixture(
    array $productOverrides = [],
    array $branchOverrides = [],
    array $settingsOverrides = [],
): array {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'name' => 'Main store',
        'is_main' => true,
        'local_delivery_fee' => 5,
    ], $branchOverrides));
    $taxRate = TaxRate::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Sales tax',
        'rate' => 10,
        'is_active' => true,
        'is_inclusive' => false,
    ]);
    $settings = TenantSetting::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'tax_inclusive' => false,
        'default_tax_rate_id' => null,
        'is_storefront_enabled' => true,
        'bank_transfer_instructions' => 'Transfer to the store account and use your order reference.',
    ], $settingsOverrides));
    $product = Product::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'name' => 'Storefront item',
        'selling_price' => 10,
        'tax_rate_id' => $taxRate->id,
        'is_active' => true,
        'is_storefront_published' => true,
        'track_inventory' => true,
    ], $productOverrides));
    $inventory = Inventory::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 20,
    ]);

    return compact('tenant', 'branch', 'settings', 'taxRate', 'product', 'inventory');
}

function storefrontCheckoutSession(Tenant $tenant, Product $product, float $quantity = 2): array
{
    return [
        'storefront_checkout_idempotency_'.$tenant->id => Str::uuid()->toString(),
        'storefront_checkout_tracking_'.$tenant->id => Str::random(64),
        'storefront_cart_'.$tenant->id => [
            $product->id.':' => [
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => $quantity,
            ],
        ],
    ];
}

test('paystack checkout initializes from server totals and webhook verifies signed payment once', function () {
    $fixture = createStorefrontCheckoutFixture(settingsOverrides: [
        'paystack_enabled' => true,
        'paystack_secret' => 'sk_test_storefront_secret',
    ]);
    $fixture['tenant']->update(['currency' => 'NGN']);
    $tenant = $fixture['tenant']->fresh();
    $product = $fixture['product'];
    $session = storefrontCheckoutSession($tenant, $product);

    Http::preventStrayRequests();
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => function ($request) {
            $reference = $request['reference'];

            return Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/session',
                    'reference' => $reference,
                ],
            ]);
        },
    ]);

    $response = $this->withSession($session)->post(route('storefront.checkout.store', $tenant->slug), [
        'customer_name' => 'Paystack Shopper',
        'customer_phone' => '555-0102',
        'customer_email' => 'paystack@example.test',
        'fulfillment_method' => 'pickup',
        'payment_method' => 'paystack',
        'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
    ]);

    $response->assertRedirect('https://checkout.paystack.com/session');
    $order = StorefrontOrder::query()->sole();
    expect($order->payment_method)->toBe('paystack')
        ->and($order->payment_status)->toBe('unpaid')
        ->and($order->payment_provider)->toBe('paystack')
        ->and($order->provider_reference)->toStartWith('TSO-');

    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'id' => 123456,
                'status' => 'success',
                'reference' => $order->provider_reference,
                'amount' => 2200,
                'currency' => 'NGN',
            ],
        ]),
    ]);

    $payload = [
        'event' => 'charge.success',
        'data' => [
            'id' => 123456,
            'reference' => $order->provider_reference,
        ],
    ];
    $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha512', $rawPayload, 'sk_test_storefront_secret');

    $this->call(
        'POST',
        route('storefront.paystack.webhook'),
        server: [
            'HTTP_X_PAYSTACK_SIGNATURE' => 'invalid',
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $rawPayload,
    )
        ->assertBadRequest();

    $this->call(
        'POST',
        route('storefront.paystack.webhook'),
        server: [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $rawPayload,
    )
        ->assertOk();

    expect($order->fresh()->payment_status)->toBe('paid');

    $this->call(
        'POST',
        route('storefront.paystack.webhook'),
        server: [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $rawPayload,
    )
        ->assertOk();

    expect(StorefrontPaymentEvent::query()->where('provider_event_id', '123456')->count())->toBe(1);
});

test('checkout prices from current tenant product tax and branch settings and is idempotent', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $inventory = $fixture['inventory'];
    $session = storefrontCheckoutSession($tenant, $product);
    $idempotencyKey = $session['storefront_checkout_idempotency_'.$tenant->id];
    $trackingToken = $session['storefront_checkout_tracking_'.$tenant->id];
    $payload = [
        'customer_name' => 'Guest Shopper',
        'customer_phone' => '555-0101',
        'customer_email' => 'shopper@example.test',
        'fulfillment_method' => 'local_delivery',
        'delivery_address' => '12 Market Street',
        'payment_method' => 'bank_transfer',
        'idempotency_key' => $idempotencyKey,
    ];

    $this->withSession($session)
        ->get(route('storefront.checkout.show', $tenant->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->where('items.0.line_subtotal', 20)
            ->where('items.0.tax_amount', 2)
            ->where('items.0.line_total', 22)
            ->where('deliveryFee', '5.0000')
        );

    $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), $payload)
        ->assertRedirect(route('storefront.orders.show', [$tenant->slug, StorefrontOrder::query()->value('id')]));

    $order = StorefrontOrder::query()->sole();

    expect(Str::length($trackingToken))->toBe(64);
    expect($order->tenant_id)->toBe($tenant->id)
        ->and($order->branch_id)->toBe($fixture['branch']->id)
        ->and($order->subtotal)->toBe('20.0000')
        ->and($order->tax_amount)->toBe('2.0000')
        ->and($order->delivery_fee)->toBe('5.0000')
        ->and($order->total_amount)->toBe('27.0000')
        ->and($order->customer_name)->toBe('Guest Shopper')
        ->and($order->delivery_address)->toBe('12 Market Street')
        ->and($order->tracking_token_hash)->toBe(hash('sha256', $trackingToken))
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->unit_price)->toBe('10.0000')
        ->and($order->items->first()->tax_amount)->toBe('2.0000')
        ->and($order->items->first()->tax_inclusive)->toBeFalse();

    $reservation = StorefrontStockReservation::query()->sole();
    expect($reservation->status)->toBe('active')
        ->and($reservation->quantity)->toBe('2.0000');

    $this->post(route('storefront.checkout.store', $tenant->slug), $payload)
        ->assertRedirect();

    expect(StorefrontOrder::query()->count())->toBe(1)
        ->and(StorefrontStockReservation::query()->count())->toBe(1);

    $order = StorefrontOrder::query()->sole();
    $this->get(route('storefront.orders.show', [$tenant->slug, $order->id]))
        ->assertOk()
        ->assertSessionHas('storefront_order_tracking_'.$order->id, $trackingToken)
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/order')
            ->where('order.reference_no', $order->reference_no)
            ->where('order.total_amount', '27.0000')
            ->missing('order.customer_phone')
            ->missing('order.customer_email')
            ->missing('order.delivery_address')
            ->missing('order.tracking_token_hash')
            ->missing('order.idempotency_key')
            ->missing('order.payment_reference')
            ->where('order.payment_instructions', $fixture['settings']->bank_transfer_instructions)
        );

    expect((float) $inventory->fresh()->quantity)->toBe(20.0);
});

test('staff storefront order actions are recorded in the audit log', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);
    $order = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'status' => 'pending',
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->post(route('tenant.storefront.orders.accept', [$tenant->id, $order->id]))
        ->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'event' => 'storefront.order.status_changed',
        'auditable_type' => StorefrontOrder::class,
        'auditable_id' => $order->id,
    ]);
});

test('checkout summary and order use the same tax-inclusive product pricing', function () {
    $fixture = createStorefrontCheckoutFixture(
        settingsOverrides: ['tax_inclusive' => true],
        branchOverrides: ['local_delivery_fee' => 0],
    );
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $session = storefrontCheckoutSession($tenant, $product);

    $this->withSession($session)
        ->get(route('storefront.checkout.show', $tenant->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->where('items.0.line_subtotal', 20)
            ->where('items.0.tax_amount', 1.8182)
            ->where('items.0.line_total', 20)
            ->where('items.0.tax_inclusive', true)
        );

    $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '555-0101',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'cash',
            'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        ])
        ->assertRedirect();

    $order = StorefrontOrder::query()->sole();

    expect($order->subtotal)->toBe('20.0000')
        ->and($order->tax_amount)->toBe('1.8182')
        ->and($order->total_amount)->toBe('20.0000')
        ->and($order->items->first()->line_total)->toBe('20.0000');
});

test('checkout rejects client price and tenant ownership fields', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $session = storefrontCheckoutSession($tenant, $product);
    $payload = [
        'customer_name' => 'Guest Shopper',
        'customer_phone' => '555-0101',
        'fulfillment_method' => 'pickup',
        'payment_method' => 'cash',
        'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        'tenant_id' => Str::ulid()->toString(),
        'branch_id' => Str::ulid()->toString(),
        'total_amount' => 0.01,
    ];

    $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), $payload)
        ->assertSessionHasErrors(['tenant_id', 'branch_id', 'total_amount']);

    expect(StorefrontOrder::query()->count())->toBe(0);
});

test('storefront cart writes are rate limited per tenant and client', function () {
    $tenant = Tenant::factory()->create();
    $url = route('storefront.cart.items.store', $tenant->slug);

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->post($url, [])->assertSessionHasErrors('product_id');
    }

    $this->post($url, [])->assertTooManyRequests();
});

test('checkout writes are rate limited without blocking another tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $url = route('storefront.checkout.store', $tenant->slug);

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->post($url, [])->assertSessionHasErrors('customer_name');
    }

    $this->post($url, [])->assertTooManyRequests();
    $this->post(route('storefront.checkout.store', $otherTenant->slug), [])
        ->assertSessionHasErrors('customer_name');
});

test('guest order status lookups are rate limited', function () {
    $fixture = createStorefrontCheckoutFixture();
    $order = StorefrontOrder::factory()->create([
        'tenant_id' => $fixture['tenant']->id,
        'branch_id' => $fixture['branch']->id,
    ]);
    $url = route('storefront.orders.show', [$fixture['tenant']->slug, $order->id]);

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->get($url)->assertNotFound();
    }

    $this->get($url)->assertTooManyRequests();
});

test('guest order tracking requires the correct session credential', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $session = storefrontCheckoutSession($tenant, $product);
    $order = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'tracking_token_hash' => hash(
            'sha256',
            $session['storefront_checkout_tracking_'.$tenant->id],
        ),
    ]);
    $url = route('storefront.orders.show', [$tenant->slug, $order->id]);

    $this->get($url)->assertNotFound();

    $this->withSession([
        'storefront_order_tracking_'.$order->id => Str::random(64),
    ])->get($url)->assertNotFound();

    $this->withSession([
        'storefront_order_tracking_'.$order->id => $session['storefront_checkout_tracking_'.$tenant->id],
    ])->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/order')
            ->where('order.reference_no', $order->reference_no)
            ->missing('order.customer_name')
            ->missing('order.customer_phone')
            ->missing('order.customer_email')
            ->missing('order.delivery_address')
            ->missing('order.tracking_token_hash')
            ->missing('order.idempotency_key')
            ->missing('order.cancellation_note')
            ->missing('order.refund_note')
        );
});

test('checkout cannot add another tenant product and does not expose another tenant cart', function () {
    $first = createStorefrontCheckoutFixture();
    $second = createStorefrontCheckoutFixture();

    $this->post(route('storefront.cart.items.store', $first['tenant']->slug), [
        'product_id' => $second['product']->id,
        'quantity' => 1,
    ])->assertNotFound();

    $this->withSession([
        'storefront_cart_'.$second['tenant']->id => [
            $second['product']->id.':' => [
                'product_id' => $second['product']->id,
                'variant_id' => null,
                'quantity' => 1,
            ],
        ],
    ])->get(route('storefront.cart.show', $first['tenant']->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/cart')
            ->has('items', 0)
        );
});

test('checkout rejects insufficient unreserved stock', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $fixture['inventory']->update(['quantity' => 1]);
    $session = storefrontCheckoutSession($tenant, $product, 2);

    $checkoutResponse = $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '555-0101',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'cash',
            'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        ])
        ->assertSessionHasErrors('items');

    expect(StorefrontOrder::query()->count())->toBe(0)
        ->and(StorefrontStockReservation::query()->count())->toBe(0);
});

test('inventory reductions cannot consume stock reserved for online orders', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $inventory = $fixture['inventory'];
    $session = storefrontCheckoutSession($tenant, $product);

    $checkoutResponse = $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '555-0101',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'cash',
            'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        ]);
    $checkoutResponse->assertRedirect()->assertSessionHasNoErrors();
    expect(StorefrontOrder::query()->count())->toBe(1)
        ->and(StorefrontStockReservation::active()->count())->toBe(1);

    expect(fn () => AdjustInventoryAction::handle(
        tenantId: $tenant->id,
        branchId: $fixture['branch']->id,
        productId: $product->id,
        quantityChanged: -19,
        referenceId: (string) Str::ulid(),
        type: 'sale',
    ))->toThrow(ValidationException::class);

    expect((float) $inventory->fresh()->quantity)->toBe(20.0);

    $adjustInventory = new AdjustInventoryAction;

    expect(fn () => $adjustInventory->execute([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'product_id' => $product->id,
        'quantity_delta' => -19,
    ]))->toThrow(ValidationException::class);

    expect((float) $inventory->fresh()->quantity)->toBe(20.0)
        ->and(StorefrontStockReservation::active()->count())->toBe(1);
});

test('confirmed offline payments consume stock and paid cancellations require an external refund record', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $inventory = $fixture['inventory'];
    $session = storefrontCheckoutSession($tenant, $product, 2);

    $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '555-0101',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'bank_transfer',
            'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        ])
        ->assertRedirect();

    $order = StorefrontOrder::query()->sole();
    $actor = User::factory()->create();
    $manager = app(ManageStorefrontOrderAction::class);

    $manager->confirmPayment($tenant, $order, $actor, 'BANK-REF-100');

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and((float) $inventory->fresh()->quantity)->toBe(18.0)
        ->and($order->items()->first()->stockReservation->fresh()->status)->toBe('consumed');

    expect(fn () => $manager->cancel($tenant, $order, $actor, 'Order cancelled', false, null))
        ->toThrow(ValidationException::class);

    $cancelledOrder = $manager->cancel(
        $tenant,
        $order,
        $actor,
        'Customer changed their mind',
        true,
        'Bank transfer returned to the customer externally.',
    );

    expect($cancelledOrder->status)->toBe('cancelled')
        ->and($cancelledOrder->payment_status)->toBe('refunded')
        ->and($cancelledOrder->refund_handled_externally)->toBeTrue()
        ->and((float) $inventory->fresh()->quantity)->toBe(20.0)
        ->and(StorefrontOrderEvent::query()->where('storefront_order_id', $order->id)->count())->toBe(3);
});

test('terminal storefront orders can erase customer personal data without deleting the order', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $actor = User::factory()->create();
    $order = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'status' => 'completed',
        'customer_name' => 'Private Customer',
        'customer_phone' => '555-0199',
        'customer_email' => 'private@example.test',
        'delivery_address' => 'Private Address',
    ]);

    $erased = app(EraseStorefrontCustomerDataAction::class)->erase($tenant, $order, $actor);

    expect($erased->customer_name)->toBe('[deleted]')
        ->and($erased->customer_phone)->toBe('[deleted]')
        ->and($erased->customer_email)->toBeNull()
        ->and($erased->delivery_address)->toBeNull()
        ->and($erased->customer_user_id)->toBeNull()
        ->and($erased->personal_data_erased_at)->not->toBeNull()
        ->and(StorefrontOrder::query()->whereKey($order->id)->exists())->toBeTrue()
        ->and(StorefrontOrderEvent::query()
            ->where('storefront_order_id', $order->id)
            ->where('event', 'customer_data_erased')
            ->exists())->toBeTrue();
});

test('active storefront orders cannot erase customer personal data', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $order = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'status' => 'ready',
    ]);

    expect(fn () => app(EraseStorefrontCustomerDataAction::class)->erase($tenant, $order))
        ->toThrow(ValidationException::class);
});

test('customer data retention command only erases old terminal orders', function () {
    config()->set('storefront.customer_data_retention_days', 30);
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $oldOrder = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'status' => 'completed',
        'customer_email' => 'old@example.test',
        'created_at' => now()->subDays(31),
        'updated_at' => now()->subDays(31),
    ]);
    $recentOrder = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'status' => 'completed',
        'customer_email' => 'recent@example.test',
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);
    $activeOrder = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'status' => 'ready',
        'customer_email' => 'active@example.test',
        'created_at' => now()->subDays(31),
        'updated_at' => now()->subDays(31),
    ]);

    $this->artisan('storefront:purge-customer-data')
        ->assertSuccessful();

    expect($oldOrder->refresh()->personal_data_erased_at)->not->toBeNull()
        ->and($oldOrder->customer_email)->toBeNull()
        ->and($recentOrder->refresh()->personal_data_erased_at)->toBeNull()
        ->and($recentOrder->customer_email)->toBe('recent@example.test')
        ->and($activeOrder->refresh()->personal_data_erased_at)->toBeNull()
        ->and($activeOrder->customer_email)->toBe('active@example.test');
});

test('cash payment can only be confirmed when the order is ready for handoff', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $inventory = $fixture['inventory'];
    $session = storefrontCheckoutSession($tenant, $product);
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '555-0101',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'cash',
            'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        ])
        ->assertRedirect();
    $order = StorefrontOrder::query()->sole();
    $sessionData = ['active_tenant_id' => $tenant->id];

    $this->actingAs($user)
        ->withSession($sessionData)
        ->post(route('tenant.storefront.orders.ready', [$tenant->id, $order->id]))
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->payment_status)->toBe('unpaid');

    $this->actingAs($user)
        ->withSession($sessionData)
        ->post(route('tenant.storefront.orders.payment', [$tenant->id, $order->id]))
        ->assertSessionHasErrors('payment_status');

    expect($order->fresh()->payment_status)->toBe('unpaid')
        ->and((float) $inventory->fresh()->quantity)->toBe(20.0);

    $this->actingAs($user)
        ->withSession($sessionData)
        ->post(route('tenant.storefront.orders.accept', [$tenant->id, $order->id]))
        ->assertRedirect();

    $this->actingAs($user)
        ->withSession($sessionData)
        ->post(route('tenant.storefront.orders.ready', [$tenant->id, $order->id]))
        ->assertRedirect();

    $this->actingAs($user)
        ->withSession($sessionData)
        ->post(route('tenant.storefront.orders.payment', [$tenant->id, $order->id]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe('ready')
        ->and($order->fresh()->payment_status)->toBe('paid')
        ->and((float) $inventory->fresh()->quantity)->toBe(18.0);
});

test('staff order management is permission protected and isolated by tenant', function () {
    $tenant = createStorefrontCheckoutFixture();
    $otherTenant = createStorefrontCheckoutFixture();
    $otherOrder = StorefrontOrder::factory()->create([
        'tenant_id' => $otherTenant['tenant']->id,
        'branch_id' => $otherTenant['branch']->id,
    ]);
    $owner = User::factory()->create();
    attachUserToTenant($owner, $tenant['tenant']);
    $session = ['active_tenant_id' => $tenant['tenant']->id];

    $this->actingAs($owner)
        ->withSession($session)
        ->get(route('tenant.storefront.orders.index', $tenant['tenant']->id))
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/orders/index')
            ->has('orders', 0)
        );

    $this->actingAs($owner)
        ->withSession($session)
        ->post(route('tenant.storefront.orders.accept', [$tenant['tenant']->id, $otherOrder->id]))
        ->assertNotFound();

    $member = User::factory()->create();
    TenantUser::query()->create([
        'tenant_id' => $tenant['tenant']->id,
        'user_id' => $member->id,
        'is_owner' => false,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->withSession($session)
        ->get(route('tenant.storefront.orders.index', $tenant['tenant']->id))
        ->assertForbidden();
});

test('order reassignment moves reservations and recalculates the branch delivery fee', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $session = storefrontCheckoutSession($tenant, $product);
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '555-0101',
            'fulfillment_method' => 'local_delivery',
            'delivery_address' => '12 Market Street',
            'payment_method' => 'bank_transfer',
            'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        ])
        ->assertRedirect();
    $order = StorefrontOrder::query()->sole();
    $destinationBranch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'North store',
        'is_main' => false,
        'local_delivery_fee' => 8,
    ]);
    $destinationInventory = Inventory::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $destinationBranch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 10,
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->patch(route('tenant.storefront.orders.branch', [$tenant->id, $order->id]), [
            'branch_id' => $destinationBranch->id,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($order->fresh()->branch_id)->toBe($destinationBranch->id)
        ->and($order->fresh()->delivery_fee)->toBe('8.0000')
        ->and($order->fresh()->total_amount)->toBe('30.0000')
        ->and($order->items()->first()->stockReservation->fresh()->inventory_id)->toBe($destinationInventory->id)
        ->and(StorefrontStockReservation::active()->where('inventory_id', $fixture['inventory']->id)->count())->toBe(0);
});

test('order reassignment rejects a branch with insufficient unreserved stock', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $session = storefrontCheckoutSession($tenant, $product);
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '555-0101',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'cash',
            'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        ])
        ->assertRedirect();
    $order = StorefrontOrder::query()->sole();
    $destinationBranch = Branch::factory()->create([
        'tenant_id' => $tenant->id,
        'is_main' => false,
    ]);
    Inventory::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $destinationBranch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->from(route('tenant.storefront.orders.index', $tenant->id))
        ->patch(route('tenant.storefront.orders.branch', [$tenant->id, $order->id]), [
            'branch_id' => $destinationBranch->id,
        ])
        ->assertSessionHasErrors('branch_id');

    expect($order->fresh()->branch_id)->toBe($fixture['branch']->id)
        ->and($order->fresh()->delivery_fee)->toBe('0.0000')
        ->and(StorefrontStockReservation::active()->where('inventory_id', $fixture['inventory']->id)->count())->toBe(1);
});

test('a guest tracking session cannot view another tenant order', function () {
    $first = createStorefrontCheckoutFixture();
    $second = createStorefrontCheckoutFixture();
    $order = StorefrontOrder::factory()->create([
        'tenant_id' => $second['tenant']->id,
        'branch_id' => $second['branch']->id,
    ]);

    $this->withSession([
        'storefront_order_tracking_'.$order->id => Str::random(64),
    ])->get(route('storefront.orders.show', [$first['tenant']->slug, $order->id]))
        ->assertNotFound();
});

test('staff can update local delivery tracking and guests can view it', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $product = $fixture['product'];
    $session = storefrontCheckoutSession($tenant, $product);
    $trackingToken = $session['storefront_checkout_tracking_'.$tenant->id];
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);

    $this->withSession($session)
        ->post(route('storefront.checkout.store', $tenant->slug), [
            'customer_name' => 'Guest Shopper',
            'customer_phone' => '555-0101',
            'fulfillment_method' => 'local_delivery',
            'delivery_address' => '12 Market Street',
            'payment_method' => 'bank_transfer',
            'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
        ])
        ->assertRedirect();
    $order = StorefrontOrder::query()->sole();

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->patch(route('tenant.storefront.orders.delivery-tracking', [$tenant->id, $order->id]), [
            'carrier_name' => 'Swift Courier',
            'tracking_number' => 'SWIFT-12345',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($order->fresh()->carrier_name)->toBe('Swift Courier')
        ->and($order->fresh()->tracking_number)->toBe('SWIFT-12345');

    $this->withSession([
        'storefront_order_tracking_'.$order->id => $trackingToken,
    ])->get(route('storefront.orders.show', [$tenant->slug, $order->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.fulfillment_method', 'local_delivery')
            ->where('order.carrier_name', 'Swift Courier')
            ->where('order.tracking_number', 'SWIFT-12345')
        );
});

test('delivery tracking rejects pickup orders and terminal orders', function () {
    $fixture = createStorefrontCheckoutFixture();
    $tenant = $fixture['tenant'];
    $user = User::factory()->create();
    attachUserToTenant($user, $tenant);
    $pickupOrder = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'fulfillment_method' => 'pickup',
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->from(route('tenant.storefront.orders.index', $tenant->id))
        ->patch(route('tenant.storefront.orders.delivery-tracking', [$tenant->id, $pickupOrder->id]), [
            'carrier_name' => 'Courier',
            'tracking_number' => 'TRACK-1',
        ])
        ->assertSessionHasErrors('tracking_number');

    $completedOrder = StorefrontOrder::factory()->create([
        'tenant_id' => $tenant->id,
        'branch_id' => $fixture['branch']->id,
        'fulfillment_method' => 'local_delivery',
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $tenant->id])
        ->patch(route('tenant.storefront.orders.delivery-tracking', [$tenant->id, $completedOrder->id]), [
            'carrier_name' => 'Courier',
            'tracking_number' => 'TRACK-2',
        ])
        ->assertSessionHasErrors('tracking_number');

    expect($pickupOrder->fresh()->tracking_number)->toBeNull()
        ->and($completedOrder->fresh()->tracking_number)->toBeNull();
});

test('delivery tracking is isolated by tenant', function () {
    $fixture = createStorefrontCheckoutFixture();
    $other = createStorefrontCheckoutFixture();
    $user = User::factory()->create();
    attachUserToTenant($user, $fixture['tenant']);
    $otherOrder = StorefrontOrder::factory()->create([
        'tenant_id' => $other['tenant']->id,
        'branch_id' => $other['branch']->id,
        'fulfillment_method' => 'local_delivery',
    ]);

    $this->actingAs($user)
        ->withSession(['active_tenant_id' => $fixture['tenant']->id])
        ->patch(route('tenant.storefront.orders.delivery-tracking', [$fixture['tenant']->id, $otherOrder->id]), [
            'carrier_name' => 'Courier',
            'tracking_number' => 'CROSS-TENANT',
        ])
        ->assertNotFound();

    expect($otherOrder->fresh()->tracking_number)->toBeNull();
});

it('rejects mismatched Paystack transaction details', function (string $mismatch) {
    $fixture = createStorefrontCheckoutFixture(settingsOverrides: [
        'paystack_enabled' => true,
        'paystack_secret' => 'sk_test_storefront_secret',
    ]);
    $fixture['tenant']->update(['currency' => 'NGN']);
    $tenant = $fixture['tenant']->fresh();
    $session = storefrontCheckoutSession($tenant, $fixture['product']);

    Http::preventStrayRequests();
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => function ($request) {
            return Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/session',
                    'reference' => $request['reference'],
                ],
            ]);
        },
    ]);

    $response = $this->withSession($session)->post(route('storefront.checkout.store', $tenant->slug), [
        'customer_name' => 'Paystack Shopper',
        'customer_phone' => '555-0102',
        'customer_email' => 'paystack@example.test',
        'fulfillment_method' => 'pickup',
        'payment_method' => 'paystack',
        'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
    ]);
    $response->assertRedirect('https://checkout.paystack.com/session');
    $order = StorefrontOrder::query()->sole();

    $transaction = [
        'id' => 123456,
        'status' => 'success',
        'reference' => $order->provider_reference,
        'amount' => 2200,
        'currency' => 'NGN',
    ];
    $transaction[$mismatch === 'reference' ? 'reference' : $mismatch] =
        match ($mismatch) {
            'amount' => 2201,
            'currency' => 'USD',
            'reference' => 'TSO-DIFFERENT',
        };

    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => $transaction,
        ]),
    ]);

    $payload = [
        'event' => 'charge.success',
        'data' => [
            'id' => 123456,
            'reference' => $order->provider_reference,
        ],
    ];
    $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call(
        'POST',
        route('storefront.paystack.webhook'),
        server: [
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $rawPayload, 'sk_test_storefront_secret'),
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $rawPayload,
    )->assertOk();

    expect($order->fresh()->payment_status)->toBe('unpaid')
        ->and(StorefrontPaymentEvent::query()->count())->toBe(0);
})->with(['amount', 'currency', 'reference']);

test('Paystack provider failure leaves payment unpaid and a later verification can retry successfully', function () {
    $fixture = createStorefrontCheckoutFixture(settingsOverrides: [
        'paystack_enabled' => true,
        'paystack_secret' => 'sk_test_storefront_secret',
    ]);
    $fixture['tenant']->update(['currency' => 'NGN']);
    $tenant = $fixture['tenant']->fresh();
    $session = storefrontCheckoutSession($tenant, $fixture['product']);

    Http::preventStrayRequests();
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => function ($request) {
            return Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/session',
                    'reference' => $request['reference'],
                ],
            ]);
        },
    ]);

    $this->withSession($session)->post(route('storefront.checkout.store', $tenant->slug), [
        'customer_name' => 'Paystack Shopper',
        'customer_phone' => '555-0102',
        'customer_email' => 'paystack@example.test',
        'fulfillment_method' => 'pickup',
        'payment_method' => 'paystack',
        'idempotency_key' => $session['storefront_checkout_idempotency_'.$tenant->id],
    ]);
    $order = StorefrontOrder::query()->sole();

    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::sequence()
            ->push(['status' => false], 503)
            ->push([
                'status' => true,
                'data' => [
                    'id' => 789012,
                    'status' => 'success',
                    'reference' => $order->provider_reference,
                    'amount' => 2200,
                    'currency' => 'NGN',
                ],
            ]),
    ]);

    $firstResponse = $this->get(route('storefront.paystack.callback', [
        'tenant' => $tenant->slug,
        'reference' => $order->provider_reference,
    ]));
    $firstResponse->assertRedirect(route('storefront.orders.show', [$tenant->slug, $order->id]));

    expect($order->fresh()->payment_status)->toBe('unpaid');

    app(PaystackStorefrontCheckout::class)->verify($order->fresh());

    expect($order->fresh()->payment_status)->toBe('paid')
        ->and(StorefrontPaymentEvent::query()->where('provider_event_id', '789012')->count())->toBe(1);
});
