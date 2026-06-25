<?php

use App\Models\Operacional\CashRegister;
use App\Models\Operacional\CashMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list cash registers', function () {
    CashRegister::factory()->count(5)->create();

    actingAs($this->user)
        ->getJson('/api/operacional/cash-registers')
        ->assertOk();
});

it('can filter cash registers by status', function () {
    CashRegister::factory()->create(['status' => 'open']);
    CashRegister::factory()->create(['status' => 'closed']);

    actingAs($this->user)
        ->getJson('/api/operacional/cash-registers?filters[status]=open')
        ->assertOk();
});

it('can open a cash register', function () {
    $data = [
        'register_date' => now()->addDay()->format('Y-m-d'),
        'opening_balance' => 1000.00,
        'operator' => 'João Silva',
    ];

    actingAs($this->user)
        ->postJson('/api/operacional/cash-registers', $data)
        ->assertCreated()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.total_credits', '0.00')
        ->assertJsonPath('data.total_debits', '0.00');
});

it('validates cash register opening', function () {
    CashRegister::factory()->create(['register_date' => now()->format('Y-m-d')]);

    actingAs($this->user)
        ->postJson('/api/operacional/cash-registers', [
            'register_date' => now()->format('Y-m-d'),
            'opening_balance' => 1000.00,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['register_date']);
});

it('can show a cash register with movements', function () {
    $register = CashRegister::factory()->create();
    CashMovement::factory()->count(2)->create(['cash_register_id' => $register->id]);

    actingAs($this->user)
        ->getJson("/api/operacional/cash-registers/{$register->id}?include=movements")
        ->assertOk()
        ->assertJsonPath('data.movements.0.id', $register->movements->first()->id);
});

it('can close a cash register', function () {
    $register = CashRegister::factory()->create(['status' => 'open', 'opening_balance' => 1000]);
    CashMovement::factory()->create(['cash_register_id' => $register->id, 'type' => 'credit', 'amount' => 500]);
    CashMovement::factory()->create(['cash_register_id' => $register->id, 'type' => 'debit', 'amount' => 200]);

    actingAs($this->user)
        ->putJson("/api/operacional/cash-registers/{$register->id}/close", [
            'closing_balance' => 1300.00,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.closing_balance', '1300.00');
});
