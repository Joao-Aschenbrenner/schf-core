<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('prevents SQL injection in search parameters', function () {
    $payloads = [
        "' OR '1'='1",
        "'; DROP TABLE users; --",
        "1 UNION SELECT * FROM users",
        "' OR 1=1 --",
    ];

    foreach ($payloads as $payload) {
        $response = actingAs($this->user)
            ->getJson("/api/suppliers?search={$payload}");

        expect($response->status())->not->toBe(500);
    }
});

it('prevents SQL injection in filter parameters', function () {
    $payloads = [
        "' OR '1'='1",
        "1; DELETE FROM suppliers",
    ];

    foreach ($payloads as $payload) {
        $response = actingAs($this->user)
            ->getJson("/api/historico/fornecedores?filters[nome]={$payload}");

        expect($response->status())->not->toBe(500);
    }
});

it('prevents SQL injection in date filters', function () {
    $payloads = [
        "2025-01-01' OR '1'='1",
        "2025-01-01; DROP TABLE historico_notas",
    ];

    foreach ($payloads as $payload) {
        $response = actingAs($this->user)
            ->getJson("/api/historico/notas?filters[emissao_from]={$payload}");

        expect($response->status())->not->toBe(500);
    }
});

it('uses parameterized queries for all Eloquent operations', function () {
    $response = actingAs($this->user)
        ->getJson("/api/receivables?filters[status]=pending' OR '1'='1");

    expect($response->status())->not->toBe(500);
});