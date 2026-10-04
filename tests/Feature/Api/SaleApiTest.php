<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->tenant = Tenant::factory()->create();

    TenantUser::query()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'is_owner' => true,
        'is_active' => true,
        'joined_at' => now(),
    ]);
});

test('tenant users can list sales through the api', function () {
    $branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);

    Sale::query()->create([
        'tenant_id' => $this->tenant->id,
        'branch_id' => $branch->id,
        'customer_id' => Customer::factory()->create(['tenant_id' => $this->tenant->id])->id,
        'reference_no' => 'SALE-1001',
        'idempotency_key' => '8df1d46e-6522-4da6-9d07-489ad9d8db6d',
        'status' => 'completed',
        'subtotal' => '10.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '0.8000',
        'total_amount' => '10.8000',
        'paid_amount' => '10.8000',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'sold_by' => $this->user->id,
        'sold_at' => now(),
    ]);

    Sale::query()->create([
        'tenant_id' => Tenant::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'customer_id' => Customer::factory()->create()->id,
        'reference_no' => 'SALE-9999',
        'idempotency_key' => 'c96457f8-eaa9-4fd7-814b-ea2cbd7b6b46',
        'status' => 'completed',
        'subtotal' => '99.0000',
        'discount_amount' => '0.0000',
        'tax_amount' => '7.9200',
        'total_amount' => '106.9200',
        'paid_amount' => '106.9200',
        'change_amount' => '0.0000',
        'outstanding_amount' => '0.0000',
        'sold_by' => $this->user->id,
        'sold_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-ID' => $this->tenant->id])
        ->getJson('/api/v1/sales');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.reference_no', 'SALE-1001')
        ->assertJsonPath('data.0.total_amount', '10.8000');
});

test('tenant users can create a sale through the api', function () {
    $branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
    $register = Register::factory()->create(['tenant_id' => $this->tenant->id, 'branch_id' => $branch->id]);
    $session = RegisterSession::factory()->create([
        'tenant_id' => $this->tenant->id,
        'branch_id' => $branch->id,
        'register_id' => $register->id,
        'opened_by' => $this->user->id,
        'status' => 'open',
    ]);
    $paymentMethod = PaymentMethod::query()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Cash',
        'type' => 'cash',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Tenant-ID' => $this->tenant->id])
        ->postJson('/api/v1/sales', [
            'branch_id' => $branch->id,
            'register_session_id' => $session->id,
            'customer_id' => $customer->id,
            'reference_no' => 'SALE-2001',
            'idempotency_key' => '9d4b8801-797d-4409-8e9d-f3d8fa5d8ad9',
            'items' => [[
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => '2.0000',
                'unit_price' => '9.0000',
                'discount_amount' => '0.0000',
                'tax_amount' => '1.4400',
                'tax_rate' => '0.0800',
                'total_amount' => '19.4400',
            ]],
            'payments' => [[
                'payment_method_id' => $paymentMethod->id,
                'amount' => '19.4400',
            ]],
            'note' => 'Test sale',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.reference_no', 'SALE-2001')
        ->assertJsonPath('data.total_amount', '19.4400')
        ->assertJsonPath('data.items.0.quantity', '2.0000');

    $this->assertDatabaseHas('sales', [
        'tenant_id' => $this->tenant->id,
        'reference_no' => 'SALE-2001',
        'status' => 'completed',
    ]);
});
