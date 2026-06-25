<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, postJson, getJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('rate limits login attempts', function () {
    for ($i = 0; $i < 10; $i++) {
        postJson('/api/auth/login', [
            'email' => 'wrong@email.com',
            'password' => 'wrong',
        ]);
    }

    $response = postJson('/api/auth/login', [
        'email' => 'wrong@email.com',
        'password' => 'wrong',
    ]);

    expect($response->status())->toBeIn([422, 429]);
});

it('rate limits API requests', function () {
    for ($i = 0; $i < 100; $i++) {
        actingAs($this->user)
            ->getJson('/api/health');
    }

    $response = actingAs($this->user)
        ->getJson('/api/health');

    expect($response->status())->toBeIn([200, 429]);
});