<?php

use App\Models\Operacional\Receivable;
use App\Models\Supplier;
use App\Models\BankAccount;

test('factory creates valid receivable', function () {
    $receivable = Receivable::factory()->create();

    expect($receivable)->toBeInstanceOf(Receivable::class);
    expect($receivable->amount)->toBeNumeric();
    expect($receivable->status)->toBe('pending');
});

test('receivable has correct fillable', function () {
    $receivable = new Receivable();

    expect($receivable->getFillable())->toContain('description');
    expect($receivable->getFillable())->toContain('amount');
    expect($receivable->getFillable())->toContain('due_date');
    expect($receivable->getFillable())->toContain('status');
});

test('receivable scope pending', function () {
    Receivable::factory()->count(3)->create(['status' => 'pending']);
    Receivable::factory()->count(2)->create(['status' => 'received']);

    expect(Receivable::pending()->count())->toBe(3);
});

test('receivable scope received', function () {
    Receivable::factory()->count(2)->create(['status' => 'received']);
    Receivable::factory()->count(3)->create(['status' => 'pending']);

    expect(Receivable::received()->count())->toBe(2);
});

test('receivable scope cancelled', function () {
    Receivable::factory()->count(1)->create(['status' => 'cancelled']);
    Receivable::factory()->count(3)->create(['status' => 'pending']);

    expect(Receivable::cancelled()->count())->toBe(1);
});

test('receivable scope overdue', function () {
    Receivable::factory()->create([
        'status' => 'pending',
        'due_date' => now()->subDay()->format('Y-m-d'),
    ]);
    Receivable::factory()->create([
        'status' => 'pending',
        'due_date' => now()->addDays(10)->format('Y-m-d'),
    ]);

    expect(Receivable::overdue()->count())->toBe(1);
});

test('receivable uses soft deletes', function () {
    $receivable = Receivable::factory()->create();
    $id = $receivable->id;

    $receivable->delete();

    expect(Receivable::find($id))->toBeNull();
    expect(Receivable::withTrashed()->find($id))->not->toBeNull();
});

test('receivable casts decimal fields', function () {
    $receivable = Receivable::factory()->create(['amount' => 1500.50]);

    expect($receivable->amount)->toBe('1500.50');
});
