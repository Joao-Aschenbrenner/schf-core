<?php

use App\Models\Operacional\Provision;

test('factory creates valid provision', function () {
    $provision = Provision::factory()->create();

    expect($provision)->toBeInstanceOf(Provision::class);
    expect($provision->amount)->toBeNumeric();
    expect($provision->status)->toBe('draft');
});

test('provision has correct fillable', function () {
    $provision = new Provision();

    expect($provision->getFillable())->toContain('description');
    expect($provision->getFillable())->toContain('amount');
    expect($provision->getFillable())->toContain('due_date');
    expect($provision->getFillable())->toContain('status');
});

test('provision scope draft', function () {
    Provision::factory()->count(3)->create(['status' => 'draft']);
    Provision::factory()->count(2)->create(['status' => 'confirmed']);

    expect(Provision::draft()->count())->toBe(3);
});

test('provision scope confirmed', function () {
    Provision::factory()->count(2)->create(['status' => 'confirmed']);
    Provision::factory()->count(3)->create(['status' => 'draft']);

    expect(Provision::confirmed()->count())->toBe(2);
});

test('provision scope paid', function () {
    Provision::factory()->count(4)->create(['status' => 'paid']);
    Provision::factory()->count(1)->create(['status' => 'draft']);

    expect(Provision::paid()->count())->toBe(4);
});

test('provision scope cancelled', function () {
    Provision::factory()->count(1)->create(['status' => 'cancelled']);
    Provision::factory()->count(3)->create(['status' => 'draft']);

    expect(Provision::cancelled()->count())->toBe(1);
});

test('provision scope overdue', function () {
    Provision::factory()->create([
        'status' => 'draft',
        'due_date' => now()->subDay()->format('Y-m-d'),
    ]);
    Provision::factory()->create([
        'status' => 'confirmed',
        'due_date' => now()->subDay()->format('Y-m-d'),
    ]);
    Provision::factory()->create([
        'status' => 'draft',
        'due_date' => now()->addDays(10)->format('Y-m-d'),
    ]);

    expect(Provision::overdue()->count())->toBe(2);
});

test('provision uses soft deletes', function () {
    $provision = Provision::factory()->create();
    $id = $provision->id;

    $provision->delete();

    expect(Provision::find($id))->toBeNull();
    expect(Provision::withTrashed()->find($id))->not->toBeNull();
});
