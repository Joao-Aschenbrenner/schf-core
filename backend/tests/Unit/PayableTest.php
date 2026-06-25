<?php

use App\Models\Payable;
use App\Models\Supplier;

test('factory creates valid payable', function () {
    $payable = Payable::factory()->create();

    expect($payable)->toBeInstanceOf(Payable::class);
    expect($payable->description)->not->toBeEmpty();
    expect($payable->amount)->toBeNumeric();
    expect($payable->due_date)->not->toBeEmpty();
    expect($payable->status)->toBeIn(['draft', 'pending', 'scheduled', 'paid', 'cancelled', 'overdue']);
});

test('payable belongs to supplier', function () {
    $payable = Payable::factory()->create();

    expect($payable->supplier)->toBeInstanceOf(Supplier::class);
    expect($payable->supplier_id)->toBe($payable->supplier->id);
});

test('scope pending filters pending payables', function () {
    Payable::factory()->count(3)->create(['status' => 'pending']);
    Payable::factory()->count(2)->create(['status' => 'paid']);

    expect(Payable::pending()->count())->toBe(3);
});

test('scope overdue filters overdue payables', function () {
    Payable::factory()->create(['status' => 'overdue']);
    Payable::factory()->create(['status' => 'pending', 'due_date' => now()->subDay()->format('Y-m-d')]);
    Payable::factory()->create(['status' => 'pending', 'due_date' => now()->addDays(10)->format('Y-m-d')]);

    expect(Payable::overdue()->count())->toBe(2);
});

test('scope paid filters paid payables', function () {
    Payable::factory()->count(4)->create(['status' => 'paid']);
    Payable::factory()->count(1)->create(['status' => 'pending']);

    expect(Payable::paid()->count())->toBe(4);
});

test('payable casts decimal fields correctly', function () {
    $payable = Payable::factory()->create([
        'amount' => 1500.50,
        'discount' => 100.00,
        'interest' => 50.00,
    ]);

    expect($payable->amount)->toBe('1500.50');
    expect($payable->discount)->toBe('100.00');
    expect($payable->interest)->toBe('50.00');
});

test('payable casts date fields correctly', function () {
    $payable = Payable::factory()->create([
        'due_date' => '2026-07-15',
    ]);

    expect($payable->due_date)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('payable soft deletes', function () {
    $payable = Payable::factory()->create();
    $id = $payable->id;

    $payable->delete();

    expect(Payable::find($id))->toBeNull();
    expect(Payable::withTrashed()->find($id))->not->toBeNull();
});

test('payable has correct fillable attributes', function () {
    $payable = new Payable();

    expect($payable->getFillable())->toContain('description');
    expect($payable->getFillable())->toContain('amount');
    expect($payable->getFillable())->toContain('due_date');
    expect($payable->getFillable())->toContain('status');
    expect($payable->getFillable())->toContain('payment_method');
});
