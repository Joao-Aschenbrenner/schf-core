<?php

use App\Models\Operacional\Provision;
use App\Models\Supplier;
use App\Models\BankAccount;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list provisions', function () {
    Provision::factory()->count(5)->create();

    actingAs($this->user)
        ->getJson('/api/operacional/provisions')
        ->assertOk();
});

it('can filter provisions by status', function () {
    Provision::factory()->create(['status' => 'draft']);
    Provision::factory()->create(['status' => 'confirmed']);

    actingAs($this->user)
        ->getJson('/api/operacional/provisions?filters[status]=draft')
        ->assertOk();
});

it('can filter provisions by provision_type', function () {
    Provision::factory()->create(['provision_type' => 'payroll']);
    Provision::factory()->create(['provision_type' => 'tax']);

    actingAs($this->user)
        ->getJson('/api/operacional/provisions?filters[provision_type]=payroll')
        ->assertOk();
});

it('can create a provision', function () {
    $data = [
        'description' => 'Provisão folha pagamento',
        'amount' => 50000.00,
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'provision_type' => 'payroll',
    ];

    actingAs($this->user)
        ->postJson('/api/operacional/provisions', $data)
        ->assertCreated()
        ->assertJsonPath('data.description', 'Provisão folha pagamento');
});

it('validates provision creation', function () {
    actingAs($this->user)
        ->postJson('/api/operacional/provisions', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['description', 'amount', 'due_date']);
});

it('can show a provision', function () {
    $provision = Provision::factory()->create();

    actingAs($this->user)
        ->getJson("/api/operacional/provisions/{$provision->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $provision->id);
});

it('can update a provision', function () {
    $provision = Provision::factory()->create();

    actingAs($this->user)
        ->putJson("/api/operacional/provisions/{$provision->id}", [
            'description' => 'Descrição Atualizada',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Descrição Atualizada');
});

it('can confirm a provision', function () {
    $provision = Provision::factory()->create(['status' => 'draft']);

    actingAs($this->user)
        ->postJson("/api/operacional/provisions/{$provision->id}/confirm")
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');
});

it('can pay a provision', function () {
    $provision = Provision::factory()->create(['status' => 'confirmed', 'amount' => 10000]);

    actingAs($this->user)
        ->postJson("/api/operacional/provisions/{$provision->id}/pay", [
            'paid_amount' => $provision->amount,
            'paid_at' => now()->format('Y-m-d'),
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.paid_amount', $provision->amount);
});

it('can cancel a provision', function () {
    $provision = Provision::factory()->create(['status' => 'draft']);

    actingAs($this->user)
        ->postJson("/api/operacional/provisions/{$provision->id}/cancel")
        ->assertOk()
        ->assertJsonPath('message', 'Provision cancelled.');
});
