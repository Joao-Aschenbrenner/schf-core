<?php

use App\Models\User;
use function Pest\Laravel\{postJson, getJson, actingAs};

it('can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@hospital.test',
        'password' => bcrypt('password123'),
    ]);

    $response = postJson('/api/auth/login', [
        'email' => 'test@hospital.test',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user', 'token']);
});

it('cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@hospital.test',
        'password' => bcrypt('password123'),
    ]);

    postJson('/api/auth/login', [
        'email' => 'test@hospital.test',
        'password' => 'wrong-password',
    ])->assertStatus(422);
});

it('cannot login with inactive account', function () {
    $user = User::factory()->create([
        'email' => 'inactive@hospital.test',
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    postJson('/api/auth/login', [
        'email' => 'inactive@hospital.test',
        'password' => 'password123',
    ])->assertStatus(422);
});

it('can logout', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/auth/logout')
        ->assertOk();
});

it('can get current user', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('requires authentication for protected routes', function () {
    getJson('/api/me')->assertUnauthorized();
});


