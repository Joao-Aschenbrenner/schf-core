<?php

use App\Models\Operacional\CashMovement;
use App\Models\Operacional\CashRegister;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\{actingAs, getJson, postJson, seed};

beforeEach(function () {
    seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
});

it('can list cash movements', function () {
    CashMovement::factory()->count(5)->create();

    actingAs($this->user)
        ->getJson('/api/operacional/cash-movements')
        ->assertOk();
});

it('can filter cash movements by cash_register_id', function () {
    $register = CashRegister::factory()->create();
    CashMovement::factory()->count(2)->create(['cash_register_id' => $register->id]);
    CashMovement::factory()->create();

    actingAs($this->user)
        ->getJson("/api/operacional/cash-movements?filters[cash_register_id]={$register->id}")
        ->assertOk();
});

it('can filter cash movements by type', function () {
    CashMovement::factory()->create(['type' => 'credit']);
    CashMovement::factory()->create(['type' => 'debit']);

    actingAs($this->user)
        ->getJson('/api/operacional/cash-movements?filters[type]=credit')
        ->assertOk();
});

it('can create a cash movement', function () {
    $register = CashRegister::factory()->create();

    $data = [
        'cash_register_id' => $register->id,
        'type' => 'credit',
        'amount' => 500.00,
        'description' => 'Venda à vista',
        'category' => 'venda',
        'payment_method' => 'pix',
    ];

    actingAs($this->user)
        ->postJson('/api/operacional/cash-movements', $data)
        ->assertCreated()
        ->assertJsonPath('data.amount', '500.00');
});

it('validates cash movement creation', function () {
    $register = CashRegister::factory()->create();

    actingAs($this->user)
        ->postJson('/api/operacional/cash-movements', [
            'cash_register_id' => $register->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'amount', 'description']);
});
