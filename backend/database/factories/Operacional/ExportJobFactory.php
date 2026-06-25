<?php

namespace Database\Factories\Operacional;

use App\Models\Operacional\ExportJob;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExportJobFactory extends Factory
{
    protected $model = ExportJob::class;

    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'type' => fake()->randomElement(['csv', 'xlsx', 'pdf']),
            'module' => fake()->randomElement(['notas', 'baixas', 'bancos', 'caixa', 'relatorios']),
            'status' => 'pending',
            'parameters' => null,
            'file_path' => null,
            'file_size' => null,
            'error' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
