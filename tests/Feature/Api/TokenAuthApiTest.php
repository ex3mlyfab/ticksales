<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

test('login issues a sanctum bearer token', function () {
    $user = User::factory()->create([
        'email' => 'token@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'token@example.com',
        'password' => 'password123',
        'device_name' => 'pos-handheld',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.email', 'token@example.com')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    expect($response->json('token'))->toBeString()
        ->and($response->json('token'))->not->toBeEmpty();
});

test('login rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'invalid@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'invalid@example.com',
        'password' => 'wrong-password',
    ])->assertUnauthorized()->assertJsonPath('message', 'Invalid credentials.');
});

test('bearer token can access protected routes and resolves tenant from header', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'Token Tenant']);
    attachUserToTenant($user, $tenant);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('tenant.id', $tenant->id)
        ->assertJsonPath('tenant.name', 'Token Tenant');
});

test('bearer token resolves first active tenant without header', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['name' => 'First Tenant']);
    attachUserToTenant($user, $tenant);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('tenant.id', $tenant->id);
});

test('requests without a bearer token are unauthenticated', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

test('logout revokes the active bearer token', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachUserToTenant($user, $tenant);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out.');

    // Real HTTP requests are separate processes, so reset the memoized guard
    // state the way a fresh request cycle would before asserting the token
    // is no longer usable.
    Auth::forgetGuards();

    // The same token is no longer valid.
    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});

test('sanctum actingAs keeps existing session-based API behaviour', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    attachUserToTenant($user, $tenant);

    Sanctum::actingAs($user, ['*']);

    $this->withHeaders(['X-Tenant-ID' => $tenant->id])
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});
