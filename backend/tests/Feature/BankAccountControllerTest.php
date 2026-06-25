<?php

use App\Models\BankAccount;
use App\Models\HealthPlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, deleteJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list bank accounts', function () {
    BankAccount::factory()->count(3)->create();

    actingAs($this->user)
        ->getJson('/api/bank-accounts')
        ->assertOk();
});

it('can create a bank account', function () {
    $data = [
        'bank_code' => '001',
        'bank_name' => 'Banco do Brasil',
        'agency' => '1234',
        'account' => '12345',
        'digit' => '0',
        'type' => 'checking',
        'holder_name' => 'Teste',
        'current_balance' => 10000.00,
    ];

    actingAs($this->user)
        ->postJson('/api/bank-accounts', $data)
        ->assertCreated()
        ->assertJsonPath('data.bank_name', 'Banco do Brasil');
});

it('validates bank account creation', function () {
    actingAs($this->user)
        ->postJson('/api/bank-accounts', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['bank_code', 'bank_name', 'agency', 'account']);
});

it('can show a bank account', function () {
    $account = BankAccount::factory()->create();

    actingAs($this->user)
        ->getJson("/api/bank-accounts/{$account->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $account->id);
});

it('can update a bank account', function () {
    $account = BankAccount::factory()->create();

    actingAs($this->user)
        ->putJson("/api/bank-accounts/{$account->id}", [
            'bank_name' => 'Banco Atualizado',
        ])
        ->assertOk()
        ->assertJsonPath('data.bank_name', 'Banco Atualizado');
});

it('can delete a bank account', function () {
    $account = BankAccount::factory()->create();

    actingAs($this->user)
        ->deleteJson("/api/bank-accounts/{$account->id}")
        ->assertOk();
});
