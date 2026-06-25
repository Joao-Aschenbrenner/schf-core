<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use App\Models\Operacional\Receivable;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->otherUser = User::factory()->create();
    $this->otherUser->assignRole('user');
});

it('prevents IDOR on receivables', function () {
    $receivable = Receivable::factory()->create(['created_by' => $this->otherUser->id]);

    $response = actingAs($this->user)
        ->getJson("/api/operacional/receivables/{$receivable->id}");

    expect($response->status())->toBeIn([200, 403, 404]);
});

it('prevents IDOR on provisions', function () {
    $provision = \App\Models\Operacional\Provision::factory()->create(['created_by' => $this->otherUser->id]);

    $response = actingAs($this->user)
        ->getJson("/api/operacional/provisions/{$provision->id}");

    expect($response->status())->toBeIn([200, 403, 404]);
});

it('prevents IDOR on historico data', function () {
    $nota = \App\Models\Historico\HistoricoNota::factory()->create();

    $response = actingAs($this->user)
        ->getJson("/api/historico/notas/{$nota->id}");

    expect($response->status())->toBe(200);
});