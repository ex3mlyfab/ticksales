<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Database\Seeders\DemoSeeder;

use function Pest\Laravel\seed;

test('demo seeder creates a complete tenant with sample data', function () {
    seed(DemoSeeder::class);

    $tenant = Tenant::query()->where('slug', 'demo-store')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->plan)->toBe('enterprise')
        ->and($tenant->isActive())->toBeTrue();

    expect(User::query()->where('email', 'owner@ticksales.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'manager@ticksales.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'cashier@ticksales.test')->exists())->toBeTrue();

    expect(TenantUser::query()->where('tenant_id', $tenant->id)->count())->toBe(3)
        ->and(Branch::query()->where('tenant_id', $tenant->id)->count())->toBe(1)
        ->and(Product::query()->where('tenant_id', $tenant->id)->count())->toBe(8)
        ->and(Inventory::query()->where('tenant_id', $tenant->id)->count())->toBe(8)
        ->and(Customer::query()->where('tenant_id', $tenant->id)->count())->toBe(1);

    $branch = Branch::query()->where('tenant_id', $tenant->id)->firstOrFail();

    expect(RegisterSession::query()->where('tenant_id', $tenant->id)->where('status', 'open')->exists())->toBeTrue()
        ->and(Sale::query()->where('tenant_id', $tenant->id)->count())->toBe(6)
        ->and(Expense::query()->where('tenant_id', $tenant->id)->count())->toBe(1)
        // Sales decrement stock: 8 products seeded with 25-100 units, 6 sales of 1-3 units each.
        ->and(Inventory::query()->where('tenant_id', $tenant->id)->where('branch_id', $branch->id)->sum('quantity'))->toBeGreaterThan(0);
});
