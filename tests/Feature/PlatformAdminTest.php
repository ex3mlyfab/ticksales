<?php

use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('platform admins can access tenant administration routes', function () {
    $user = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    $response = $this->actingAs($user)->get('/admin/tenants');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/tenants/index')
    );
});

test('non platform admins cannot access tenant administration routes', function () {
    $user = User::factory()->create([
        'is_platform_admin' => false,
    ]);

    $this->actingAs($user)
        ->get('/admin/tenants')
        ->assertForbidden();
});

test('platform admins can create tenants from the admin controller', function () {
    $user = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    $response = $this->actingAs($user)->post('/admin/tenants', [
        'name' => 'Northwind Retail',
        'slug' => 'northwind-retail',
        'email' => 'hello@northwind.test',
        'currency' => 'USD',
        'timezone' => 'UTC',
        'locale' => 'en',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('tenants', ['slug' => 'northwind-retail']);
});

test('platform admin tenant CRUD is recorded in the audit log', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant = Tenant::factory()->create();

    $this->actingAs($admin)
        ->put("/admin/tenants/{$tenant->id}", [
            'name' => 'Acme Updated',
            'slug' => 'acme-updated',
            'email' => 'acme@example.test',
            'currency' => 'EUR',
            'is_active' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'tenant_id' => $tenant->id,
        'event' => 'tenant.updated',
        'auditable_type' => Tenant::class,
        'auditable_id' => $tenant->id,
    ]);

    $this->actingAs($admin)
        ->delete("/admin/tenants/{$tenant->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'tenant_id' => $tenant->id,
        'event' => 'tenant.deleted',
        'auditable_type' => Tenant::class,
        'auditable_id' => $tenant->id,
    ]);
});

test('tenant creation is recorded in the audit log', function () {
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $this->actingAs($admin)->post('/admin/tenants', [
        'name' => 'Audit Retail',
        'slug' => 'audit-retail',
        'email' => 'audit@retail.test',
        'currency' => 'USD',
    ])->assertRedirect();

    $created = Tenant::query()->where('slug', 'audit-retail')->first();

    expect($created)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'tenant_id' => $created->id,
        'event' => 'tenant.created',
        'auditable_type' => Tenant::class,
        'auditable_id' => $created->id,
    ]);
});
