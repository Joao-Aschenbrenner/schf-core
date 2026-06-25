<?php

use App\Models\Operacional\BankOperation;
use App\Models\BankAccount;
use App\Models\Payable;
use App\Models\Operacional\Receivable;
use App\Models\Operacional\BankInvestment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list bank operations', function () {
    BankOperation::factory()->count(5)->create();

        actingAs($this->user)
        ->getJson('/api/operacional/bank-operations')
        ->assertOk();
});

it('can filter bank operations by bank_account_id', function () {
    $account = BankAccount::factory()->create();
    BankOperation::factory()->count(2)->create(['bank_account_id' => $account->id]);
    BankOperation::factory()->create();

        actingAs($this->user)
        ->getJson("/api/operacional/bank-operations?filters[bank_account_id]={$account->id}")
        ->assertOk();
});

it('can filter bank operations by type', function () {
    BankOperation::factory()->create(['type' => 'credit']);
    BankOperation::factory()->create(['type' => 'debit']);

        actingAs($this->user)
        ->getJson('/api/operacional/bank-operations?filters[type]=credit')
        ->assertOk();
});

it('can filter bank operations by date range', function () {
    BankOperation::factory()->create(['operation_date' => '2025-06-01']);
    BankOperation::factory()->create(['operation_date' => '2025-07-15']);

        actingAs($this->user)
        ->getJson('/api/operacional/bank-operations?filters[operation_date_from]=2025-06-01&filters[operation_date_to]=2025-06-30')
        ->assertOk();
});

it('can create a bank operation', function () {
    $bankAccount = BankAccount::factory()->create();

    $data = [
        'bank_account_id' => $bankAccount->id,
        'type' => 'credit',
        'amount' => 10000.00,
        'description' => 'Recebimento cliente',
        'operation_date' => now()->format('Y-m-d'),
    ];

        actingAs($this->user)
        ->postJson('/api/operacional/bank-operations', $data)
        ->assertCreated()
        ->assertJsonPath('data.amount', '10000.00');
});

it('validates bank operation creation', function () {
        actingAs($this->user)
        ->postJson('/api/operacional/bank-operations', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['bank_account_id', 'type', 'amount', 'operation_date']);
});

it('can generate extrato bancario operacional', function () {
    $bankAccount = BankAccount::factory()->create(['current_balance' => 20000.00]);
    BankOperation::factory()->create([
        'bank_account_id' => $bankAccount->id,
        'type' => 'credit',
        'amount' => 5000,
        'operation_date' => now()->format('Y-m-d'),
    ]);
    BankOperation::factory()->create([
        'bank_account_id' => $bankAccount->id,
        'type' => 'debit',
        'amount' => 2000,
        'operation_date' => now()->format('Y-m-d'),
    ]);

        actingAs($this->user)
        ->getJson("/api/operacional/bank-operations/extrato?bank_account_id={$bankAccount->id}&data_from=" . now()->format('Y-m-d') . "&data_to=" . now()->format('Y-m-d'))
        ->assertOk()
        ->assertJsonPath('data.saldo_inicial', '20000.00')
        ->assertJsonPath('data.total_creditos', 5000)
        ->assertJsonPath('data.total_debitos', 2000)
        ->assertJsonPath('data.saldo_final', 23000);
});

it('requires valid parameters for extrato bancario operacional', function () {
        actingAs($this->user)
        ->getJson('/api/operacional/bank-operations/extrato')
        ->assertStatus(422);
});
