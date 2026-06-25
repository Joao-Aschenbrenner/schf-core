<?php

use App\Models\HealthPlan;
use App\Models\ResourcePlan;

test('factory creates valid health plan', function () {
    $plan = HealthPlan::factory()->create();

    expect($plan)->toBeInstanceOf(HealthPlan::class);
    expect($plan->name)->not->toBeEmpty();
    expect($plan->code)->not->toBeEmpty();
    expect($plan->is_active)->toBeTrue();
});

test('scope active filters health plans', function () {
    HealthPlan::factory()->count(3)->create(['is_active' => true]);
    HealthPlan::factory()->count(2)->create(['is_active' => false]);

    expect(HealthPlan::active()->count())->toBe(3);
});

test('health plan has many resource plans', function () {
    $plan = HealthPlan::factory()->create();
    ResourcePlan::factory()->count(2)->create(['health_plan_id' => $plan->id]);

    expect($plan->resourcePlans)->toHaveCount(2);
});

test('health plan casts decimal fields', function () {
    $plan = HealthPlan::factory()->create([
        'balance' => 50000.00,
        'committed_balance' => 10000.00,
    ]);

    expect($plan->balance)->toBe('50000.00');
    expect($plan->committed_balance)->toBe('10000.00');
});

test('health plan soft deletes', function () {
    $plan = HealthPlan::factory()->create();
    $id = $plan->id;

    $plan->delete();

    expect(HealthPlan::find($id))->toBeNull();
    expect(HealthPlan::withTrashed()->find($id))->not->toBeNull();
});
