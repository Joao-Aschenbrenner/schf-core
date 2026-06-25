<?php

use App\Models\Operacional\BankInvestment;
use App\Models\BankAccount;
use App\Models\Historico\HistoricoConta;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list bank investments', function () {
    BankInvestment::factory()->count(5)->create();

        actingAs($this->user)
        ->getJson('/api/operacional/bank-investments')
        ->assertOk();
});

it('can filter bank investments by status', function () {
    BankInvestment::factory()->create(['status' => 'active']);
    BankInvestment::factory()->create(['status' => 'redeemed']);

        actingAs($this->user)
        ->getJson('/api/operacional/bank-investments?filters[status]=active')
        ->assertOk();
});

it('can filter bank investments by investment_type', function () {
    BankInvestment::factory()->create(['investment_type' => 'cdb']);
    BankInvestment::factory()->create(['investment_type' => 'lci_lca']);

        actingAs($this->user)
        ->getJson('/api/operacional/bank-investments?filters[investment_type]=cdb')
        ->assertOk();
});

it('can create a bank investment', function () {
    $bankAccount = BankAccount::factory()->create();

    $data = [
        'bank_account_id' => $bankAccount->id,
        'description' => 'CDB 30 dias',
        'investment_type' => 'cdb',
        'amount' => 50000.00,
        'yield_rate' => 12.50,
        'start_date' => now()->format('Y-m-d'),
        'maturity_date' => now()->addDays(30)->format('Y-m-d'),
    ];

        actingAs($this->user)
        ->postJson('/api/operacional/bank-investments', $data)
        ->assertCreated()
        ->assertJsonPath('data.description', 'CDB 30 dias');
});

it('validates bank investment creation', function () {
        actingAs($this->user)
        ->postJson('/api/operacional/bank-investments', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['bank_account_id', 'description', 'amount', 'start_date']);
});

it('can show a bank investment with operations', function () {
    $investment = BankInvestment::factory()->create();
    \App\Models\Operacional\BankOperation::factory()->count(2)->create(['bank_investment_id' => $investment->id]);

        actingAs($this->user)
        ->getJson("/api/operacional/bank-investments/{$investment->id}?include=operations")
        ->assertOk()
        ->assertJsonPath('data.operations.0.id', $investment->operations->first()->id);
});

it('can update a bank investment', function () {
    $investment = BankInvestment::factory()->create();

        actingAs($this->user)
        ->putJson("/api/operacional/bank-investments/{$investment->id}", [
            'description' => 'CDB Atualizado',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'CDB Atualizado');
});

it('can redeem a bank investment', function () {
    $investment = BankInvestment::factory()->create(['status' => 'active', 'amount' => 50000]);

        actingAs($this->user)
        ->postJson("/api/operacional/bank-investments/{$investment->id}/redeem", [
            'redeemed_amount' => 51000.00,
            'redeemed_at' => now()->format('Y-m-d'),
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'redeemed')
        ->assertJsonPath('data.redeemed_amount', '51000.00');
});

it('can delete a bank investment', function () {
    $investment = BankInvestment::factory()->create();

        actingAs($this->user)
        ->deleteJson("/api/operacional/bank-investments/{$investment->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Investment deleted.');
});
