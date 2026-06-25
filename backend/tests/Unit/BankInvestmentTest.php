<?php

use App\Models\Operacional\BankInvestment;

test('factory creates valid bank investment', function () {
    $investment = BankInvestment::factory()->create();

    expect($investment)->toBeInstanceOf(BankInvestment::class);
    expect($investment->amount)->toBeNumeric();
    expect($investment->status)->toBe('active');
});

test('bank investment scope active', function () {
    BankInvestment::factory()->count(3)->create(['status' => 'active']);
    BankInvestment::factory()->count(1)->create(['status' => 'redeemed']);

    expect(BankInvestment::active()->count())->toBe(3);
});

test('bank investment scope redeemed', function () {
    BankInvestment::factory()->count(2)->create(['status' => 'redeemed']);
    BankInvestment::factory()->count(3)->create(['status' => 'active']);

    expect(BankInvestment::redeemed()->count())->toBe(2);
});

test('bank investment can be redeemed', function () {
    $investment = BankInvestment::factory()->create(['status' => 'active', 'amount' => 10000]);

    $investment->update([
        'status' => 'redeemed',
        'redeemed_amount' => 10500,
        'redeemed_at' => now()->toDateString(),
    ]);

    expect($investment->fresh()->status)->toBe('redeemed');
    expect($investment->fresh()->redeemed_amount)->toBe('10500.00');
});

test('bank investment casts decimal fields', function () {
    $investment = BankInvestment::factory()->create(['amount' => 12345.67]);

    expect($investment->amount)->toBe('12345.67');
});
