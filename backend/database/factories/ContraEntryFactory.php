<?php

namespace Database\Factories;

use App\Models\ContraEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContraEntryFactory extends Factory
{
    protected $model = ContraEntry::class;

    public function definition(): array
    {
        $types = ['correction', 'reversal', 'adjustment'];

        return [
            'model_type' => fake()->randomElement([
                \App\Models\Payable::class,
                \App\Models\Nfe::class,
            ]),
            'model_id' => fake()->randomNumber(),
            'type' => fake()->randomElement($types),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'original_amount' => fake()->randomFloat(2, 10, 10000),
            'reason' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}
