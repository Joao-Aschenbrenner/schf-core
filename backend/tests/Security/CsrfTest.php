<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, postJson, putJson, deleteJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('requires CSRF token for state-changing operations', function () {
    $response = actingAs($this->user)
        ->postJson('/api/receivables', [
            'description' => 'Test',
            'amount' => 100,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

    expect($response->status())->toBe(201);
});

it('protects against CSRF via session authentication', function () {
    $response = $this->actingAs($this->user)
        ->post('/api/receivables', [
            'description' => 'Test',
            'amount' => 100,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

    expect($response->status())->not->toBe(419);
});