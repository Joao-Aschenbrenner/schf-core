<?php

use App\Models\PreLaunch;
use App\Models\Supplier;

test('factory creates valid pre-launch', function () {
    $preLaunch = PreLaunch::factory()->create();

    expect($preLaunch)->toBeInstanceOf(PreLaunch::class);
    expect($preLaunch->description)->not->toBeEmpty();
    expect($preLaunch->estimated_amount)->toBeNumeric();
    expect($preLaunch->status)->toBeIn(['projected', 'confirmed', 'converted', 'cancelled']);
});

test('pre-launch belongs to supplier', function () {
    $preLaunch = PreLaunch::factory()->create();

    expect($preLaunch->supplier)->toBeInstanceOf(Supplier::class);
    expect($preLaunch->supplier_id)->toBe($preLaunch->supplier->id);
});

test('pre-launch projected scope works', function () {
    PreLaunch::factory()->count(3)->create(['status' => 'projected']);
    PreLaunch::factory()->count(2)->create(['status' => 'confirmed']);

    expect(PreLaunch::where('status', 'projected')->count())->toBe(3);
});

test('pre-launch soft deletes', function () {
    $preLaunch = PreLaunch::factory()->create();
    $id = $preLaunch->id;

    $preLaunch->delete();

    expect(PreLaunch::find($id))->toBeNull();
    expect(PreLaunch::withTrashed()->find($id))->not->toBeNull();
});

test('pre-launch casts decimal and date fields', function () {
    $preLaunch = PreLaunch::factory()->create([
        'estimated_amount' => 5000.00,
        'expected_date' => '2026-07-01',
    ]);

    expect($preLaunch->estimated_amount)->toBe('5000.00');
    expect($preLaunch->expected_date)->toBeInstanceOf(\Carbon\Carbon::class);
});
