<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use App\Models\Operacional\Receivable;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, postJson, putJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('prevents mass assignment on protected fields', function () {
    $payload = [
        'description' => 'Test',
        'amount' => 100,
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'id' => 999999,
        'created_at' => '2020-01-01',
        'updated_at' => '2020-01-01',
    ];

    $response = actingAs($this->user)
        ->postJson('/api/operacional/receivables', $payload);

    expect($response->status())->toBe(201);

    $data = $response->json('data');
    expect($data['id'])->not->toBe(999999);
    expect($data['created_at'])->not->toBe('2020-01-01');
});

it('prevents mass assignment on user_id fields', function () {
    $otherUser = User::factory()->create();

    $payload = [
        'description' => 'Test',
        'amount' => 100,
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'created_by' => $otherUser->id,
        'approved_by' => $otherUser->id,
    ];

    $response = actingAs($this->user)
        ->postJson('/api/operacional/receivables', $payload);

    expect($response->status())->toBe(201);

    $data = $response->json('data');
    expect($data['created_by'])->toBe($this->user->id);
    expect($data['approved_by'])->toBeNull();
});

it('prevents mass assignment on soft delete fields', function () {
    $receivable = Receivable::factory()->create();

    $response = actingAs($this->user)
        ->putJson("/api/operacional/receivables/{$receivable->id}", [
            'deleted_at' => now()->toDateTimeString(),
        ]);

    expect($response->status())->toBe(200);

    $receivable->refresh();
    expect($receivable->deleted_at)->toBeNull();
});