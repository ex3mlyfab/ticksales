<?php

use App\Models\User;

test('api users can authenticate and fetch their profile', function () {
    $user = User::factory()->create([
        'email' => 'api@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'api@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.email', 'api@example.com')
        ->assertJsonPath('user.id', $user->id);

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', 'api@example.com');
});
