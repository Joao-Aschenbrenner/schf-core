<?php

namespace Database\Factories;

use App\Models\ResourcePlan;
use App\Models\HealthPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourcePlanFactory extends Factory
{
    protected $model = ResourcePlan::class;

    public function definition(): array
    {
        return [
            'health_plan_id' => HealthPlan::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'allocated_amount' => fake()->randomFloat(2, 10000, 500000),
            'used_amount' => fake()->randomFloat(2, 0, 100000),
            'committed_amount' => fake()->randomFloat(2, 0, 50000),
            'start_date' => fake()->dateTimeBetween('-90 days', 'now'),
            'end_date' => fake()->dateTimeBetween('+30 days', '+365 days'),
            'is_active' => true,
        ];
    }

    public function healthPlan(HealthPlan $plan): static
    {
        return $this->state(fn () => ['health_plan_id' => $plan->id]);
    }
}
