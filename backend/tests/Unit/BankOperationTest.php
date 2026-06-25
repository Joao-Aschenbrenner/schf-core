<?php

use App\Models\Operacional\BankOperation;

test('factory creates valid bank operation', function () {
    $operation = BankOperation::factory()->create();

    expect($operation)->toBeInstanceOf(BankOperation::class);
    expect($operation->amount)->toBeNumeric();
    expect($operation->type)->toBeIn(['credit', 'debit', 'investment', 'transfer']);
});

test('bank operation scope credits', function () {
    BankOperation::factory()->count(3)->create(['type' => 'credit']);
    BankOperation::factory()->count(2)->create(['type' => 'debit']);

    expect(BankOperation::credits()->count())->toBe(3);
});

test('bank operation scope debits', function () {
    BankOperation::factory()->count(2)->create(['type' => 'debit']);
    BankOperation::factory()->count(3)->create(['type' => 'credit']);

    expect(BankOperation::debits()->count())->toBe(2);
});

test('bank operation scope investments', function () {
    BankOperation::factory()->count(1)->create(['type' => 'investment']);
    BankOperation::factory()->count(3)->create(['type' => 'credit']);

    expect(BankOperation::investments()->count())->toBe(1);
});

test('bank operation casts amount as decimal', function () {
    $operation = BankOperation::factory()->create(['amount' => 5555.99]);

    expect($operation->amount)->toBe('5555.99');
});
