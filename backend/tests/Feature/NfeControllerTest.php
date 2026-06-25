<?php

use App\Models\Nfe;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list nfes', function () {
    Nfe::factory()->count(3)->create();

    actingAs($this->user)
        ->getJson('/api/nfes')
        ->assertOk();
});

it('can create an nfe', function () {
    $supplier = Supplier::factory()->create();

    $data = [
        'nfe_key' => '12345678901234567890',
        'nfe_number' => '123456',
        'serie' => '1',
        'supplier_id' => $supplier->id,
        'emission_date' => now()->format('Y-m-d'),
        'expense_category_id' => \App\Models\ExpenseCategory::factory()->create()->id,
        'total_value' => 5000.00,
        'status' => 'pending',
    ];

    actingAs($this->user)
        ->postJson('/api/nfes', $data)
        ->assertCreated()
        ->assertJsonPath('data.nfe_number', '123456');
});

it('validates nfe creation', function () {
    actingAs($this->user)
        ->postJson('/api/nfes', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['nfe_key', 'nfe_number', 'total_value']);
});

it('can show an nfe', function () {
    $nfe = Nfe::factory()->create();

    actingAs($this->user)
        ->getJson("/api/nfes/{$nfe->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $nfe->id);
});

it('can update an nfe', function () {
    $nfe = Nfe::factory()->create();

    actingAs($this->user)
        ->putJson("/api/nfes/{$nfe->id}", [
            'description' => 'Descrição Atualizada',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'Descrição Atualizada');
});

it('can delete an nfe', function () {
    $nfe = Nfe::factory()->create();

    actingAs($this->user)
        ->deleteJson("/api/nfes/{$nfe->id}")
        ->assertOk();
});

it('can filter nfes by status', function () {
    Nfe::factory()->count(2)->create(['status' => 'pending']);
    Nfe::factory()->count(1)->create(['status' => 'classified']);

    actingAs($this->user)
        ->getJson('/api/nfes?status=pending')
        ->assertOk();
});

it('can search nfes', function () {
    Nfe::factory()->create(['description' => 'NF-e de Medicamentos']);
    Nfe::factory()->create(['description' => 'NF-e de Materiais']);

    actingAs($this->user)
        ->getJson('/api/nfes?search=Medicamentos')
        ->assertOk();
});
