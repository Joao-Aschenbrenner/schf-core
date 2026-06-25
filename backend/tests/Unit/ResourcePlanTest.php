<?php

use App\Models\ResourcePlan;
use App\Models\HealthPlan;

test('factory creates valid resource plan', function () {
    $plan = ResourcePlan::factory()->create();

    expect($plan)->toBeInstanceOf(ResourcePlan::class);
    expect($plan->name)->not->toBeEmpty();
    expect($plan->allocated_amount)->toBeNumeric();
    expect($plan->is_active)->toBeTrue();
});

test('resource plan belongs to health plan', function () {
    $healthPlan = HealthPlan::factory()->create();
    $plan = ResourcePlan::factory()->healthPlan($healthPlan)->create();

    expect($plan->healthPlan)->toBeInstanceOf(HealthPlan::class);
    expect($plan->health_plan_id)->toBe($healthPlan->id);
});

test('resource plan casts decimal fields', function () {
    $plan = ResourcePlan::factory()->create([
        'allocated_amount' => 100000.00,
        'used_amount' => 25000.00,
        'committed_amount' => 15000.00,
    ]);

    expect($plan->allocated_amount)->toBe('100000.00');
    expect($plan->used_amount)->toBe('25000.00');
    expect($plan->committed_amount)->toBe('15000.00');
});

test('resource plan soft deletes', function () {
    $plan = ResourcePlan::factory()->create();
    $id = $plan->id;

    $plan->delete();

    expect(ResourcePlan::find($id))->toBeNull();
    expect(ResourcePlan::withTrashed()->find($id))->not->toBeNull();
});
