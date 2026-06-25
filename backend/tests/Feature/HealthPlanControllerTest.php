<?php

use App\Models\HealthPlan;
use App\Models\ResourcePlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list health plans', function () {
    HealthPlan::factory()->count(3)->create();

    actingAs($this->user)
        ->getJson('/api/health-plans')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can create a health plan', function () {
    $data = [
        'name' => 'Convênio Teste',
        'code' => 'CT001',
        'type' => 'convenio',
        'balance' => 50000.00,
    ];

    actingAs($this->user)
        ->postJson('/api/health-plans', $data)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Convênio Teste');
});

it('validates health plan creation', function () {
    actingAs($this->user)
        ->postJson('/api/health-plans', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'code']);
});

it('can show a health plan', function () {
    $plan = HealthPlan::factory()->create();

    actingAs($this->user)
        ->getJson("/api/health-plans/{$plan->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $plan->id);
});

it('can update a health plan', function () {
    $plan = HealthPlan::factory()->create();

    actingAs($this->user)
        ->putJson("/api/health-plans/{$plan->id}", [
            'name' => 'Nome Atualizado',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nome Atualizado');
});

it('can delete a health plan', function () {
    $plan = HealthPlan::factory()->create();

    actingAs($this->user)
        ->deleteJson("/api/health-plans/{$plan->id}")
        ->assertOk();
});

it('can add resource plan to health plan', function () {
    $plan = HealthPlan::factory()->create();

    $data = [
        'name' => 'Recurso Teste',
        'allocated_amount' => 100000.00,
    ];

    actingAs($this->user)
        ->postJson("/api/health-plans/{$plan->id}/resource-plans", $data)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Recurso Teste');
});

it('can check health plan balance', function () {
    $plan = HealthPlan::factory()->create(['balance' => 50000.00]);

    actingAs($this->user)
        ->getJson("/api/health-plans/{$plan->id}/balance")
        ->assertOk()
        ->assertJsonStructure(['data' => ['allocated', 'used', 'committed', 'available', 'usage_percent']]);
});

it('can search health plans', function () {
    HealthPlan::factory()->create(['name' => 'Hospital ABC']);
    HealthPlan::factory()->create(['name' => 'Clínica XYZ']);

    actingAs($this->user)
        ->getJson('/api/health-plans?search=Hospital')
        ->assertOk();
});
