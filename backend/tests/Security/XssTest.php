<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, postJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('sanitizes XSS in description fields', function () {
    $xssPayloads = [
        '<script>alert("XSS")</script>',
        '<img src=x onerror=alert(1)>',
        'javascript:alert(1)',
        '<svg onload=alert(1)>',
        '"><script>alert(1)</script>',
    ];

    foreach ($xssPayloads as $payload) {
        $response = actingAs($this->user)
            ->postJson('/api/receivables', [
                'description' => $payload,
                'amount' => 100,
                'due_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

        expect($response->status())->toBe(201);

        $data = $response->json('data');
        expect($data['description'])->not->toContain('<script>');
    }
});

it('sanitizes XSS in notes fields', function () {
    $payload = '<script>alert("XSS")</script>';

    $response = actingAs($this->user)
        ->postJson('/api/provisions', [
            'description' => 'Test',
            'amount' => 100,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'notes' => $payload,
        ]);

    expect($response->status())->toBe(201);

    $data = $response->json('data');
    expect($data['notes'])->not->toContain('<script>');
});

it('sanitizes XSS in supplier name', function () {
    $payload = '<script>alert("XSS")</script>';

    $response = actingAs($this->user)
        ->postJson('/api/suppliers', [
            'name' => $payload,
            'cnpj' => '12345678000199',
        ]);

    expect($response->status())->toBe(201);

    $data = $response->json('data');
    expect($data['name'])->not->toContain('<script>');
});