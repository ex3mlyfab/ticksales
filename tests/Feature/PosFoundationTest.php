<?php

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Tenant;
use App\Models\User;

test('payment methods, registers, and sessions can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id]);
    $cashier = User::factory()->create();

    $cash = PaymentMethod::query()->create([
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
        'opening_balance' => '150.0000',
        'status' => 'open',
        'opened_at' => now(),
    ]);

    expect($cash->tenant_id)->toBe($tenant->id)
        ->and($cash->type)->toBe('cash')
        ->and($register->branch_id)->toBe($branch->id)
        ->and($register->name)->toBe('Counter 1')
        ->and($session->register_id)->toBe($register->id)
        ->and($session->opened_by)->toBe($cashier->getKey())
        ->and($session->status)->toBe('open');
});
