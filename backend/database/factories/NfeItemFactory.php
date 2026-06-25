<?php

namespace Database\Factories;

use App\Models\NfeItem;
use App\Models\Nfe;
use Illuminate\Database\Eloquent\Factories\Factory;

class NfeItemFactory extends Factory
{
    protected $model = NfeItem::class;

    public function definition(): array
    {
        return [
            'nfe_id' => Nfe::factory(),
            'code' => fake()->numerify('#####'),
            'ncm' => fake()->numerify('########'),
            'cfop' => fake()->randomElement(['1101', '1102', '1401', '2101', '5101', '5102']),
            'description' => fake()->words(3, true),
            'unit' => fake()->randomElement(['UN', 'KG', 'LT', 'MT', 'M2', 'M3']),
            'quantity' => fake()->randomFloat(3, 1, 1000),
            'unit_price' => fake()->randomFloat(4, 1, 10000),
            'total_price' => fake()->randomFloat(2, 10, 50000),
            'discount' => 0,
            'icms' => fake()->randomFloat(2, 0, 5000),
            'ipi' => 0,
            'pis' => fake()->randomFloat(2, 0, 500),
            'cofins' => fake()->randomFloat(2, 0, 500),
        ];
    }
}
