<?php

use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('a member lands on the dashboard of the tenant in the url', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
    $tenantB = Tenant::factory()->create(['name' => 'Beta', 'slug' => 'beta']);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenantA);
    attachUserToTenant($user, $tenantB);

    actingAs($user)
        ->get("/t/{$tenantB->id}/dashboard")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('tenant.id', $tenantB->id)
            ->where('tenant.name', 'Beta')
            ->where('tenants', fn ($tenants) => count($tenants) === 2
                && collect($tenants)->firstWhere('id', $tenantA->id)['is_current'] === false
                && collect($tenants)->firstWhere('id', $tenantB->id)['is_current'] === true)
        );

    // The requested tenant becomes the active tenant for the session.
    expect(session('active_tenant_id'))->toBe($tenantB->id);
});

test('landing on a tenant dashboard makes it the active tenant for subsequent pages', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Alpha']);
    $tenantB = Tenant::factory()->create(['name' => 'Beta']);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenantA);
    attachUserToTenant($user, $tenantB);

    actingAs($user)->get("/t/{$tenantB->id}/dashboard")->assertOk();

    // A follow-up visit to the plain dashboard keeps tenant B active.
    actingAs($user)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('tenant.id', $tenantB->id));
});

test('a non-member cannot view another tenant dashboard', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Alpha']);
    $tenantB = Tenant::factory()->create(['name' => 'Beta']);
    $user = User::factory()->create();

    attachUserToTenant($user, $tenantA);

    actingAs($user)
        ->get("/t/{$tenantB->id}/dashboard")
        ->assertNotFound();
});

test('a guest is redirected to login for a tenant dashboard', function () {
    $tenant = Tenant::factory()->create(['name' => 'Alpha']);

    $this->get("/t/{$tenant->id}/dashboard")->assertRedirect(route('login'));
});
