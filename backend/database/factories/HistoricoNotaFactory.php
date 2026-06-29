<?php

namespace Database\Factories;

use App\Models\Historico\HistoricoNota;
use Illuminate\Database\Eloquent\Factories\Factory;

class HistoricoNotaFactory extends Factory
{
    protected $model = HistoricoNota::class;

    public function definition(): array
    {
        return [
            'codigo_legado' => fake()->unique()->numberBetween(1, 999999),
            'numero' => fake()->numerify('NF-######'),
            'emissao' => fake()->date(),
            'valor' => fake()->randomFloat(2, 10, 10000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
