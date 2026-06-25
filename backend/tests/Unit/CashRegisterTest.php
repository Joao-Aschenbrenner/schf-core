<?php

use App\Models\Operacional\CashRegister;
use App\Models\Operacional\CashMovement;

test('factory creates valid cash register', function () {
    $register = CashRegister::factory()->create();

    expect($register)->toBeInstanceOf(CashRegister::class);
    expect($register->opening_balance)->toBeNumeric();
    expect($register->status)->toBe('open');
});

test('cash register has movements relationship', function () {
    $register = CashRegister::factory()->create();
    CashMovement::factory()->count(3)->create(['cash_register_id' => $register->id]);

    expect($register->movements)->toHaveCount(3);
});

test('cash register scope open', function () {
    CashRegister::factory()->create(['status' => 'open']);
    CashRegister::factory()->create(['status' => 'closed']);

    expect(CashRegister::open()->count())->toBe(1);
});

test('cash register scope closed', function () {
    CashRegister::factory()->create(['status' => 'closed']);
    CashRegister::factory()->create(['status' => 'open']);

    expect(CashRegister::closed()->count())->toBe(1);
});

test('cash register can be closed', function () {
    $register = CashRegister::factory()->create(['status' => 'open']);

    $register->update([
        'status' => 'closed',
        'closing_balance' => 500.00,
        'closed_at' => now(),
    ]);

    expect($register->fresh()->status)->toBe('closed');
    expect($register->fresh()->closing_balance)->toBe('500.00');
});
