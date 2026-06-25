<?php

namespace Database\Factories;

use App\Models\HealthPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class HealthPlanFactory extends Factory
{
    protected $model = HealthPlan::class;

    public function definition(): array
    {
        $types = ['sus', 'convenio', 'particular', 'emenda', 'municipal', 'outro'];

        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->lexify('?????'),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement($types),
            'balance' => fake()->randomFloat(2, 10000, 1000000),
            'committed_balance' => fake()->randomFloat(2, 0, 50000),
            'is_active' => true,
        ];
    }
}
