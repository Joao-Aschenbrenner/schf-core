<?php

use App\Models\Dda;

test('factory creates valid dda', function () {
    $dda = Dda::factory()->create();

    expect($dda)->toBeInstanceOf(Dda::class);
    expect($dda->document_number)->not->toBeEmpty();
    expect($dda->bar_code)->not->toBeEmpty();
    expect($dda->amount)->toBeNumeric();
    expect($dda->status)->toBeIn(['imported', 'identified', 'linked', 'rejected', 'expired']);
});

test('dda belongs to supplier', function () {
    $dda = Dda::factory()->create();

    expect($dda->supplier)->not->toBeNull();
    expect($dda->supplier_id)->toBe($dda->supplier->id);
});

test('dda casts decimal and date fields', function () {
    $dda = Dda::factory()->create([
        'amount' => 5000.00,
        'due_date' => '2026-07-15',
    ]);

    expect($dda->amount)->toBe('5000.00');
    expect($dda->due_date)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('dda soft deletes', function () {
    $dda = Dda::factory()->create();
    $id = $dda->id;

    $dda->delete();

    expect(\App\Models\Dda::find($id))->toBeNull();
    expect(\App\Models\Dda::withTrashed()->find($id))->not->toBeNull();
});
