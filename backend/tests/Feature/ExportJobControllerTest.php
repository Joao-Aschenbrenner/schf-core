<?php

use App\Models\Operacional\ExportJob;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list export jobs', function () {
    ExportJob::factory()->count(3)->create(['user_id' => $this->user->id]);

    actingAs($this->user)
        ->getJson('/api/operacional/export-jobs')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can create an export job', function () {
    $data = [
        'type' => 'csv',
        'module' => 'notas',
        'parameters' => ['date_from' => '2025-01-01', 'date_to' => '2025-12-31'],
    ];

    actingAs($this->user)
        ->postJson('/api/operacional/export-jobs', $data)
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');
});

it('validates export job creation', function () {
    actingAs($this->user)
        ->postJson('/api/operacional/export-jobs', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'module']);
});

it('can show an export job', function () {
    $job = ExportJob::factory()->create(['user_id' => $this->user->id]);

    actingAs($this->user)
        ->getJson("/api/operacional/export-jobs/{$job->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $job->id);
});

it('only shows current user export jobs', function () {
    $otherUser = User::factory()->create();
    ExportJob::factory()->create(['user_id' => $this->user->id]);
    ExportJob::factory()->create(['user_id' => $otherUser->id]);

    actingAs($this->user)
        ->getJson('/api/operacional/export-jobs')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
