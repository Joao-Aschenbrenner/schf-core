<?php

use App\Models\BankStatement;
use App\Models\BankStatementItem;

test('factory creates valid bank statement', function () {
    $statement = BankStatement::factory()->create();

    expect($statement)->toBeInstanceOf(BankStatement::class);
    expect($statement->source_file)->not->toBeEmpty();
    expect($statement->source_type)->toBeIn(['ofx', 'csv', 'manual']);
    expect($statement->status)->toBeIn(['imported', 'reconciled', 'closed']);
});

test('bank statement belongs to bank account', function () {
    $statement = BankStatement::factory()->create();

    expect($statement->bankAccount)->not->toBeNull();
    expect($statement->bank_account_id)->toBe($statement->bankAccount->id);
});

test('bank statement has many items', function () {
    $statement = BankStatement::factory()->create();
    BankStatementItem::factory()->count(5)->create(['bank_statement_id' => $statement->id]);

    expect($statement->items)->toHaveCount(5);
});

test('bank statement casts decimal fields', function () {
    $statement = BankStatement::factory()->create([
        'opening_balance' => 10000.00,
        'closing_balance' => 15000.00,
    ]);

    expect($statement->opening_balance)->toBe('10000.00');
    expect($statement->closing_balance)->toBe('15000.00');
});
