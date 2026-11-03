<?php

use App\Models\SubscriptionCheckout;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

test('platform administrators can create and update subscription plans', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $this->actingAs($admin)->post('/admin/plans', [
        'name' => 'starter',
        'display_name' => 'Starter',
        'description' => 'Core features',
        'price_monthly' => '19.99',
        'max_branches' => '2',
        'features' => "pos\nreports\npos",
        'is_active' => true,
    ])->assertRedirect('/admin/plans');

    $plan = SubscriptionPlan::query()->where('name', 'starter')->firstOrFail();
    expect($plan->features)->toBe(['pos', 'reports']);

    $this->actingAs($admin)->get('/admin/plans')->assertInertia(fn (Assert $page) => $page
        ->component('admin/plans/index')
        ->has('plans', 1)
        ->where('plans.0.tenants_count', 0)
    );

    $this->actingAs($admin)->put("/admin/plans/{$plan->id}", [
        'display_name' => 'Starter Plus',
        'description' => 'Updated features',
        'price_monthly' => '29.99',
        'max_branches' => '3',
        'features' => "pos\ninventory_advanced",
        'is_active' => false,
    ])->assertRedirect('/admin/plans');

    $plan->refresh();
    expect($plan->display_name)->toBe('Starter Plus');
    expect($plan->features)->toBe(['pos', 'inventory_advanced']);
    expect($plan->is_active)->toBeFalse();
});

test('platform administrators can configure subscription payment methods', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $this->actingAs($admin)->post('/admin/subscription-payment-methods', [
        'name' => 'Bank transfer',
        'instructions' => "Bank: Example\nAccount: 12345",
        'is_active' => true,
        'sort_order' => 1,
    ])->assertRedirect('/admin/subscription-payment-methods');

    $method = SubscriptionPaymentMethod::query()->firstOrFail();
    expect($method->instructions)->toBe("Bank: Example\nAccount: 12345");

    $this->actingAs($admin)->get('/admin/subscription-payment-methods')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/subscription-payment-methods/index')
            ->has('methods', 1)
            ->where('methods.0.name', 'Bank transfer')
        );

    $this->actingAs($admin)->put("/admin/subscription-payment-methods/{$method->id}", [
        'name' => 'Wire transfer',
        'instructions' => 'Use the updated account details.',
        'is_active' => false,
        'sort_order' => 2,
    ])->assertRedirect('/admin/subscription-payment-methods');

    $method->refresh();
    expect($method->name)->toBe('Wire transfer');
    expect($method->is_active)->toBeFalse();
});

test('platform administrators can assign a subscription plan to a tenant', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create(['plan' => null]);

    $plan = SubscriptionPlan::query()->create([
        'name' => 'growth',
        'display_name' => 'Growth',
        'price_monthly' => 49.99,
        'max_branches' => 5,
        'features' => ['pos', 'reports'],
        'is_active' => true,
    ]);

    $this->actingAs($admin)->put("/admin/tenants/{$tenant->id}", [
        'name' => $tenant->name,
        'slug' => $tenant->slug,
        'email' => $tenant->email,
        'plan' => $plan->name,
    ])->assertRedirect("/admin/tenants/{$tenant->id}");

    expect($tenant->fresh()->plan)->toBe('growth');
});

test('platform administrators can record a verified payment and extend a tenant subscription', function () {
    $this->travelTo(Carbon::parse('2026-01-30 12:00:00'));

    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create([
        'plan' => null,
        'trial_ends_at' => null,
        'subscription_ends_at' => null,
    ]);
    $plan = SubscriptionPlan::query()->create([
        'name' => 'pro',
        'display_name' => 'Pro',
        'price_monthly' => 79.99,
        'max_branches' => 10,
        'features' => ['pos', 'reports', 'multi_branch'],
        'is_active' => true,
    ]);
    $method = SubscriptionPaymentMethod::query()->create([
        'name' => 'Bank transfer',
        'instructions' => 'Transfer to the configured account.',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($admin)->post("/admin/tenants/{$tenant->id}/payments", [
        'plan_id' => $plan->id,
        'payment_method_id' => $method->id,
        'amount' => '79.99',
        'reference' => 'BANK-001',
        'paid_at' => '2026-01-31',
        'notes' => 'Payment verified by finance.',
        'confirmed' => true,
    ])->assertRedirect("/admin/tenants/{$tenant->id}");

    $payment = SubscriptionPayment::query()->firstOrFail();
    expect($payment->currency)->toBe($tenant->currency);
    expect($payment->payment_method_name)->toBe('Bank transfer');
    expect($payment->period_starts_at->toDateString())->toBe('2026-01-31');
    expect($payment->period_ends_at->toDateString())->toBe('2026-02-28');
    expect($payment->recorded_by)->toBe($admin->id);

    $updatedTenant = $tenant->fresh();
    expect($updatedTenant->plan)->toBe('pro');
    expect($updatedTenant->subscription_ends_at->toDateString())->toBe('2026-02-28');
    expect($updatedTenant->can('reports'))->toBeTrue();
    expect($updatedTenant->maxBranches())->toBe(10);
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'subscription_payment.recorded',
        'auditable_type' => SubscriptionPayment::class,
        'auditable_id' => $payment->id,
        'user_id' => $admin->id,
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($admin)->get("/admin/tenants/{$tenant->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tenants/show')
            ->has('payments', 1)
            ->where('tenant.subscription_status', 'active')
            ->where('payments.0.payment_method', 'Bank transfer')
        );
});

test('a subscription payment is rejected unless the admin confirms receipt', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create(['plan' => null]);
    $plan = SubscriptionPlan::query()->create([
        'name' => 'basic',
        'display_name' => 'Basic',
        'price_monthly' => 10,
        'max_branches' => 1,
        'features' => ['pos'],
        'is_active' => true,
    ]);
    $method = SubscriptionPaymentMethod::query()->create([
        'name' => 'Cash',
        'instructions' => 'Collect cash.',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->from("/admin/tenants/{$tenant->id}")->actingAs($admin)
        ->post("/admin/tenants/{$tenant->id}/payments", [
            'plan_id' => $plan->id,
            'payment_method_id' => $method->id,
            'amount' => '10.00',
            'paid_at' => '2026-01-31',
            'confirmed' => false,
        ])
        ->assertSessionHasErrors('confirmed');

    expect(SubscriptionPayment::query()->count())->toBe(0);
    expect($tenant->fresh()->plan)->toBeNull();
});

test('non platform administrators cannot access subscription management', function () {
    $user = User::factory()->create(['is_platform_admin' => false]);

    $this->actingAs($user)->get('/admin/plans')->assertForbidden();
    $this->actingAs($user)->get('/admin/subscription-payment-methods')->assertForbidden();
});

test('platform administrators can initialize a Paystack subscription checkout', function () {
    config([
        'services.paystack.enabled' => true,
        'services.paystack.secret' => 'paystack-test-secret',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'api.paystack.co/transaction/initialize' => fn (ClientRequest $request) => Http::response([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/session',
                'reference' => $request['reference'],
            ],
        ]),
    ]);

    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create(['plan' => null, 'currency' => 'USD']);
    $plan = SubscriptionPlan::query()->create([
        'name' => 'pro',
        'display_name' => 'Pro',
        'price_monthly' => 79.99,
        'max_branches' => 10,
        'features' => ['pos'],
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.tenants.paystack-checkout', $tenant), ['plan_id' => $plan->id])
        ->assertRedirect('https://checkout.paystack.com/session');

    $checkout = SubscriptionCheckout::query()->firstOrFail();
    expect($checkout->status)->toBe(SubscriptionCheckout::STATUS_PENDING);
    expect($checkout->amount)->toBe('79.99');
    expect($checkout->currency)->toBe('USD');
    expect($checkout->initiated_by)->toBe($admin->id);
    expect($checkout->checkout_url)->toBe('https://checkout.paystack.com/session');
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'subscription_checkout.started',
        'auditable_type' => SubscriptionCheckout::class,
        'auditable_id' => $checkout->id,
        'tenant_id' => $tenant->id,
    ]);

    Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://api.paystack.co/transaction/initialize'
        && $request['email'] === $tenant->email
        && $request['amount'] === 7999
        && $request['currency'] === 'USD'
        && $request['reference'] === $checkout->reference);
});

test('Paystack webhooks verify transactions and apply a subscription only once', function () {
    config([
        'services.paystack.enabled' => true,
        'services.paystack.secret' => 'paystack-test-secret',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'id' => 123456,
                'status' => 'success',
                'reference' => 'TS-01JWEBHOOKREFERENCE',
                'amount' => 7999,
                'currency' => 'USD',
                'paid_at' => '2026-03-15T12:00:00Z',
            ],
        ]),
    ]);

    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create([
        'plan' => null,
        'trial_ends_at' => null,
        'subscription_ends_at' => null,
        'currency' => 'USD',
    ]);
    $plan = SubscriptionPlan::query()->create([
        'name' => 'pro',
        'display_name' => 'Pro',
        'price_monthly' => 79.99,
        'max_branches' => 10,
        'features' => ['pos'],
        'is_active' => true,
    ]);
    $checkout = SubscriptionCheckout::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'initiated_by' => $admin->id,
        'reference' => 'TS-01JWEBHOOKREFERENCE',
        'status' => SubscriptionCheckout::STATUS_PENDING,
        'amount' => '79.99',
        'currency' => 'USD',
    ]);
    $payload = ['event' => 'charge.success', 'data' => ['reference' => $checkout->reference]];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha512', $body, 'paystack-test-secret');
    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
    ];

    $this->call('POST', '/api/paystack/webhook', [], [], [], $server, $body)
        ->assertOk()
        ->assertJson(['received' => true]);
    $this->call('POST', '/api/paystack/webhook', [], [], [], $server, $body)
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($checkout->fresh()->status)->toBe(SubscriptionCheckout::STATUS_PAID);
    expect($tenant->fresh()->plan)->toBe('pro');
    $this->assertDatabaseHas('subscription_payments', [
        'tenant_id' => $tenant->id,
        'gateway' => 'paystack',
        'gateway_transaction_id' => '123456',
        'reference' => $checkout->reference,
        'amount' => '79.99',
        'currency' => 'USD',
    ]);
    expect(SubscriptionPayment::query()->where('reference', $checkout->reference)->count())->toBe(1);
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'subscription_payment.recorded',
        'tenant_id' => $tenant->id,
    ]);
});

test('a Paystack callback does not apply a transaction with mismatched details', function (array $transactionChanges) {
    config([
        'services.paystack.enabled' => true,
        'services.paystack.secret' => 'paystack-test-secret',
    ]);
    Http::preventStrayRequests();
    $transaction = array_merge([
        'id' => 123456,
        'status' => 'success',
        'reference' => 'TS-01JMISMATCHREFERENCE',
        'amount' => 7999,
        'currency' => 'USD',
    ], $transactionChanges);
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => $transaction,
        ]),
    ]);

    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create(['plan' => null, 'currency' => 'USD']);
    $plan = SubscriptionPlan::query()->create([
        'name' => 'pro',
        'display_name' => 'Pro',
        'price_monthly' => 79.99,
        'max_branches' => 10,
        'features' => ['pos'],
        'is_active' => true,
    ]);
    $checkout = SubscriptionCheckout::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'initiated_by' => $admin->id,
        'reference' => 'TS-01JMISMATCHREFERENCE',
        'status' => SubscriptionCheckout::STATUS_PENDING,
        'amount' => '79.99',
        'currency' => 'USD',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.paystack.callback', ['reference' => $checkout->reference]))
        ->assertRedirect(route('admin.tenants.show', $tenant))
        ->assertSessionHas('error', 'The verified Paystack transaction did not match the checkout reference, amount, or currency.');

    expect($checkout->fresh()->status)->toBe(SubscriptionCheckout::STATUS_FAILED);
    expect($tenant->fresh()->plan)->toBeNull();
    expect(SubscriptionPayment::query()->count())->toBe(0);
})->with([
    'amount mismatch' => [['amount' => 7998]],
    'currency mismatch' => [['currency' => 'GHS']],
    'reference mismatch' => [['reference' => 'TS-01JOTHERREFERENCE']],
]);

test('Paystack rejects webhook requests with an invalid signature', function () {
    config([
        'services.paystack.enabled' => true,
        'services.paystack.secret' => 'paystack-test-secret',
    ]);
    Http::preventStrayRequests();

    $this->call(
        'POST',
        '/api/paystack/webhook',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => 'invalid'],
        '{"event":"charge.success","data":{"reference":"TS-01JINVALIDSIGNATURE"}}',
    )->assertUnauthorized();

    expect(SubscriptionPayment::query()->count())->toBe(0);
});

test('Paystack rejects fractional plan prices for zero-decimal currencies', function () {
    config([
        'services.paystack.enabled' => true,
        'services.paystack.secret' => 'paystack-test-secret',
    ]);
    Http::preventStrayRequests();

    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create(['plan' => null, 'currency' => 'XOF']);
    $plan = SubscriptionPlan::query()->create([
        'name' => 'pro',
        'display_name' => 'Pro',
        'price_monthly' => 79.99,
        'max_branches' => 10,
        'features' => ['pos'],
        'is_active' => true,
    ]);

    $this->from(route('admin.tenants.show', $tenant))
        ->actingAs($admin)
        ->post(route('admin.tenants.paystack-checkout', $tenant), ['plan_id' => $plan->id])
        ->assertSessionHasErrors([
            'payment' => 'The selected plan price must be a whole amount for XOF.',
        ]);

    expect(SubscriptionCheckout::query()->count())->toBe(0);
});
