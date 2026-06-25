<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'code' => fake()->unique()->numerify('####'),
            'description' => fake()->sentence(),
            'is_allowed_by_default' => fake()->boolean(70),
            'is_active' => true,
        ];
    }

    public function parent(ExpenseCategory $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
