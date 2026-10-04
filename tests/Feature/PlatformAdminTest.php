<?php

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
