<?php

use App\Models\Operacional\CashMovement;
use App\Models\Operacional\CashRegister;

test('factory creates valid cash movement', function () {
    $movement = CashMovement::factory()->create();

    expect($movement)->toBeInstanceOf(CashMovement::class);
    expect($movement->amount)->toBeNumeric();
    expect($movement->type)->toBeIn(['credit', 'debit']);
});

test('cash movement has cash register relationship', function () {
    $movement = CashMovement::factory()->create();

    expect($movement->cashRegister)->toBeInstanceOf(CashRegister::class);
});

test('cash movement casts amount as decimal', function () {
    $movement = CashMovement::factory()->create(['amount' => 250.75]);

    expect($movement->amount)->toBe('250.75');
});
