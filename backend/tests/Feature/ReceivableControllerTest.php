<?php

use App\Models\Operacional\Receivable;
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

it('can list receivables', function () {
    Receivable::factory()->count(5)->create();

        actingAs($this->user)
        ->getJson('/api/operacional/receivables')
        ->assertOk();
});

it('can filter receivables by status', function () {
    Receivable::factory()->create(['status' => 'pending']);
    Receivable::factory()->create(['status' => 'received']);

        actingAs($this->user)
        ->getJson('/api/operacional/receivables?filters[status]=pending')
        ->assertOk();
});

it('can filter receivables by overdue', function () {
    Receivable::factory()->create([
        'status' => 'pending',
        'due_date' => now()->subDay()->format('Y-m-d'),
    ]);
    Receivable::factory()->create([
        'status' => 'pending',
        'due_date' => now()->addDays(10)->format('Y-m-d'),
    ]);

        actingAs($this->user)
        ->getJson('/api/operacional/receivables?filters[overdue]=1')
        ->assertOk();
});

it('can create a receivable', function () {
    $data = [
        'description' => 'Recebimento de consulta',
        'amount' => 1500.00,
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'payment_method' => 'pix',
    ];

        actingAs($this->user)
        ->postJson('/api/operacional/receivables', $data)
        ->assertCreated()
        ->assertJsonPath('data.description', 'Recebimento de consulta');
});

it('validates receivable creation', function () {
        actingAs($this->user)
        ->postJson('/api/operacional/receivables', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['description', 'amount', 'due_date']);
});

it('can show a receivable', function () {
    $receivable = Receivable::factory()->create();

        actingAs($this->user)
        ->getJson("/api/operacional/receivables/{$receivable->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $receivable->id);
});

it('can update a receivable', function () {
    $receivable = Receivable::factory()->create();

        actingAs($this->user)
        ->putJson("/api/operacional/receivables/{$receivable->id}", [
            'description' => 'Descrição Atualizada',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Descrição Atualizada');
});

it('can approve a receivable', function () {
    $receivable = Receivable::factory()->create(['status' => 'pending']);

        actingAs($this->user)
        ->postJson("/api/operacional/receivables/{$receivable->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.approved_by', $this->user->id);
});

it('can receive a receivable', function () {
    $receivable = Receivable::factory()->create(['status' => 'pending']);

        actingAs($this->user)
        ->postJson("/api/operacional/receivables/{$receivable->id}/receive", [
            'received_amount' => $receivable->amount,
            'receipt_date' => now()->format('Y-m-d'),
            'payment_method' => 'pix',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'received')
        ->assertJsonPath('data.received_amount', $receivable->amount);
});

it('can cancel a receivable', function () {
    $receivable = Receivable::factory()->create(['status' => 'pending']);

        actingAs($this->user)
        ->deleteJson("/api/operacional/receivables/{$receivable->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Receivable cancelled.');
});
