<?php

namespace Database\Factories;

use App\Models\Historico\HistoricoFornecedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class HistoricoFornecedorFactory extends Factory
{
    protected $model = HistoricoFornecedor::class;

    public function definition(): array
    {
        return [
            'codigo_legado' => fake()->unique()->numberBetween(1, 999999),
            'nome' => fake()->company(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
