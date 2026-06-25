<?php

use App\Models\ContraEntry;

test('factory creates valid contra entry', function () {
    $contra = ContraEntry::factory()->create();

    expect($contra)->toBeInstanceOf(ContraEntry::class);
    expect($contra->model_type)->not->toBeEmpty();
    expect($contra->amount)->toBeNumeric();
    expect($contra->original_amount)->toBeNumeric();
    expect($contra->reason)->not->toBeEmpty();
});

test('contra entry can be approved', function () {
    $contra = ContraEntry::factory()->approved()->create();

    expect($contra->approved_by)->not->toBeNull();
    expect($contra->approved_at)->not->toBeNull();
});

test('contra entry has correct fillable attributes', function () {
    $contra = new ContraEntry();

    expect($contra->getFillable())->toContain('model_type');
    expect($contra->getFillable())->toContain('model_id');
    expect($contra->getFillable())->toContain('amount');
    expect($contra->getFillable())->toContain('reason');
});

test('contra entry casts decimal and datetime fields', function () {
    $contra = ContraEntry::factory()->approved()->create([
        'amount' => 1500.00,
        'original_amount' => 2000.00,
    ]);

    expect($contra->amount)->toBe('1500.00');
    expect($contra->original_amount)->toBe('2000.00');
    expect($contra->approved_at)->toBeInstanceOf(\Carbon\Carbon::class);
});
