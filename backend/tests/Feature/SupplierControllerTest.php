<?php

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list suppliers', function () {
    Supplier::factory()->count(3)->create();

    actingAs($this->user)
        ->getJson('/api/suppliers')
        ->assertOk();
});

it('can create a supplier', function () {
    $data = [
        'name' => 'Fornecedor Teste',
        'cnpj' => '12345678000199',
        'email' => 'fornecedor@teste.com',
        'phone' => '(14) 99999-0000',
    ];

    actingAs($this->user)
        ->postJson('/api/suppliers', $data)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Fornecedor Teste');
});

it('validates supplier creation', function () {
    actingAs($this->user)
        ->postJson('/api/suppliers', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('can show a supplier', function () {
    $supplier = Supplier::factory()->create();

    actingAs($this->user)
        ->getJson("/api/suppliers/{$supplier->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $supplier->id);
});

it('can update a supplier', function () {
    $supplier = Supplier::factory()->create();

    actingAs($this->user)
        ->putJson("/api/suppliers/{$supplier->id}", [
            'name' => 'Nome Atualizado',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nome Atualizado');
});

it('can deactivate a supplier', function () {
    $supplier = Supplier::factory()->create(['is_active' => true]);

    actingAs($this->user)
        ->deleteJson("/api/suppliers/{$supplier->id}")
        ->assertOk();
});

it('can search suppliers', function () {
    Supplier::factory()->create(['name' => 'Hospital ABC']);
    Supplier::factory()->create(['name' => 'Farmácia XYZ']);

    actingAs($this->user)
        ->getJson('/api/suppliers?search=Hospital')
        ->assertOk();
});
