<?php

use App\Models\BankAccount;
use App\Models\HealthPlan;

test('factory creates valid bank account', function () {
    $account = BankAccount::factory()->create();

    expect($account)->toBeInstanceOf(BankAccount::class);
    expect($account->bank_name)->not->toBeEmpty();
    expect($account->agency)->not->toBeEmpty();
    expect($account->account)->not->toBeEmpty();
    expect($account->is_active)->toBeTrue();
});

test('scope active filters bank accounts', function () {
    BankAccount::factory()->count(2)->create(['is_active' => true]);
    BankAccount::factory()->count(1)->create(['is_active' => false]);

    expect(BankAccount::active()->count())->toBe(2);
});

test('bank account belongs to health plan', function () {
    $plan = HealthPlan::factory()->create();
    $account = BankAccount::factory()->healthPlan($plan)->create();

    expect($account->healthPlan)->toBeInstanceOf(HealthPlan::class);
    expect($account->health_plan_id)->toBe($plan->id);
});

test('bank account casts current_balance correctly', function () {
    $account = BankAccount::factory()->create(['current_balance' => 12345.67]);

    expect($account->current_balance)->toBe('12345.67');
});

test('bank account soft deletes', function () {
    $account = BankAccount::factory()->create();
    $id = $account->id;

    $account->delete();

    expect(BankAccount::find($id))->toBeNull();
    expect(BankAccount::withTrashed()->find($id))->not->toBeNull();
});
