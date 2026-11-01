<?php

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;

/**
 * Regression coverage for P-13.
 *
 * A bare `exists:table,id` rule only proves the row exists *somewhere*, which
 * lets a client attach a record owned by a different tenant. Every tenant-owned
 * foreign key must be validated inside the active tenant.
 */
function foreignKeyTenantContext(array $overrides = []): object
{
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->create();

    attachUserToTenant($user, $tenantA);

    return (object) array_merge([
        'tenantA' => $tenantA,
        'tenantB' => $tenantB,
        'user' => $user,
    ], $overrides);
}

test('a product cannot be created with a category owned by another tenant', function () {
    $ctx = foreignKeyTenantContext();

    $foreignCategory = ProductCategory::factory()->create(['tenant_id' => $ctx->tenantB->id]);
    $ownUnit = ProductUnit::factory()->create(['tenant_id' => $ctx->tenantA->id]);

    actingAs($ctx->user)
        ->withSession(['active_tenant_id' => $ctx->tenantA->id])
        ->post('/products', [
            'name' => 'Cross Tenant Widget',
            'unit_id' => $ownUnit->id,
            'category_id' => $foreignCategory->id,
            'cost_price' => 1,
            'selling_price' => 2,
            'has_variants' => false,
            'track_inventory' => true,
            'alert_quantity' => 0,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('category_id');

    expect(Product::query()->where('name', 'Cross Tenant Widget')->exists())->toBeFalse();
});

test('a product can still be created with a category from the active tenant', function () {
    $ctx = foreignKeyTenantContext();

    $ownCategory = ProductCategory::factory()->create(['tenant_id' => $ctx->tenantA->id]);
    $ownUnit = ProductUnit::factory()->create(['tenant_id' => $ctx->tenantA->id]);

    actingAs($ctx->user)
        ->withSession(['active_tenant_id' => $ctx->tenantA->id])
        ->post('/products', [
            'name' => 'Same Tenant Widget',
            'unit_id' => $ownUnit->id,
            'category_id' => $ownCategory->id,
            'cost_price' => 1,
            'selling_price' => 2,
            'has_variants' => false,
            'track_inventory' => true,
            'alert_quantity' => 0,
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect(Product::query()->where('name', 'Same Tenant Widget')->exists())->toBeTrue();
});

test('an expense cannot be recorded against a branch owned by another tenant', function () {
    $ctx = foreignKeyTenantContext();

    $foreignBranch = Branch::factory()->create(['tenant_id' => $ctx->tenantB->id]);

    actingAs($ctx->user)
        ->withSession(['active_tenant_id' => $ctx->tenantA->id])
        ->post('/expenses', [
            'branch_id' => $foreignBranch->id,
            'description' => 'Cross tenant rent',
            'amount' => 50,
            'expense_date' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('branch_id');

    expect(Expense::query()->where('description', 'Cross tenant rent')->exists())->toBeFalse();
});

test('a product category cannot use a parent category owned by another tenant', function () {
    $ctx = foreignKeyTenantContext();

    $foreignParent = ProductCategory::factory()->create(['tenant_id' => $ctx->tenantB->id]);

    actingAs($ctx->user)
        ->withSession(['active_tenant_id' => $ctx->tenantA->id])
        ->post('/product-categories', [
            'name' => 'Cross Tenant Child',
            'parent_id' => $foreignParent->id,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('parent_id');

    expect(ProductCategory::query()->where('name', 'Cross Tenant Child')->exists())->toBeFalse();
});
