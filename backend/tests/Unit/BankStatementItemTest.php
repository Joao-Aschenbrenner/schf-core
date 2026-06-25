<?php

use App\Models\BankStatementItem;

test('factory creates valid bank statement item', function () {
    $item = BankStatementItem::factory()->create();

    expect($item)->toBeInstanceOf(BankStatementItem::class);
    expect($item->description)->not->toBeEmpty();
    expect($item->amount)->toBeNumeric();
    expect($item->type)->toBeIn(['credit', 'debit']);
    expect($item->is_reconciled)->toBeFalse();
});

test('bank statement item is debit', function () {
    $item = BankStatementItem::factory()->create(['type' => 'debit']);

    expect($item->isDebit())->toBeTrue();
    expect($item->isCredit())->toBeFalse();
});

test('bank statement item is credit', function () {
    $item = BankStatementItem::factory()->create(['type' => 'credit']);

    expect($item->isDebit())->toBeFalse();
    expect($item->isCredit())->toBeTrue();
});

test('bank statement item can be reconciled', function () {
    $item = BankStatementItem::factory()->reconciled()->create();

    expect($item->is_reconciled)->toBeTrue();
    expect($item->reconciled_at)->not->toBeNull();
    expect($item->reconciledBy)->not->toBeNull();
});

test('bank statement item belongs to bank statement', function () {
    $item = BankStatementItem::factory()->create();

    expect($item->bankStatement)->not->toBeNull();
});
