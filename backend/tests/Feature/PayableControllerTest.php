<?php

use App\Models\Payable;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list payables', function () {
    Payable::factory()->count(5)->create();

    actingAs($this->user)
        ->getJson('/api/payables')
        ->assertOk();
});

it('can create a payable', function () {
    $supplier = Supplier::factory()->create();
    $category = \App\Models\ExpenseCategory::factory()->create();

    $data = [
        'description' => 'Conta de Luz',
        'supplier_id' => $supplier->id,
        'expense_category_id' => $category->id,
        'amount' => 1500.00,
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'status' => 'pending',
    ];

    actingAs($this->user)
        ->postJson('/api/payables', $data)
        ->assertCreated()
        ->assertJsonPath('data.description', 'Conta de Luz');
});

it('validates payable creation', function () {
    actingAs($this->user)
        ->postJson('/api/payables', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['description', 'amount', 'due_date']);
});

it('can show a payable', function () {
    $payable = Payable::factory()->create();

    actingAs($this->user)
        ->getJson("/api/payables/{$payable->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $payable->id);
});

it('can update a payable', function () {
    $payable = Payable::factory()->create();

    actingAs($this->user)
        ->putJson("/api/payables/{$payable->id}", [
            'description' => 'Descrição Atualizada',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Descrição Atualizada');
});

it('can delete a payable', function () {
    $payable = Payable::factory()->create();

    actingAs($this->user)
        ->deleteJson("/api/payables/{$payable->id}")
        ->assertOk();
});

it('can filter payables by status', function () {
    Payable::factory()->count(3)->create(['status' => 'pending']);
    Payable::factory()->count(2)->create(['status' => 'paid']);

    actingAs($this->user)
        ->getJson('/api/payables?status=pending')
        ->assertOk();
});

it('can filter payables by date range', function () {
    Payable::factory()->count(2)->create(['due_date' => now()->format('Y-m-d')]);
    Payable::factory()->count(1)->create(['due_date' => now()->addDays(60)->format('Y-m-d')]);

    actingAs($this->user)
        ->getJson('/api/payables?date_from=' . now()->subDay()->format('Y-m-d') . '&date_to=' . now()->addDay()->format('Y-m-d'))
        ->assertOk();
});

it('can search payables', function () {
    Payable::factory()->create(['description' => 'Conta de Água']);
    Payable::factory()->create(['description' => 'Conta de Luz']);

    actingAs($this->user)
        ->getJson('/api/payables?search=Água')
        ->assertOk();
});
