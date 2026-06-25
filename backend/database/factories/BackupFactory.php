<?php

namespace Database\Factories;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupFactory extends Factory
{
    protected $model = Backup::class;

    public function definition(): array
    {
        return [
            'name' => 'backup_' . fake()->unique()->word() . '_' . now()->format('Ymd_His'),
            'type' => fake()->randomElement(['full', 'database', 'files']),
            'status' => fake()->randomElement(['pending', 'running', 'completed', 'failed']),
            'file_path' => 'backups/backup_' . fake()->unique()->word() . '.zip',
            'file_name' => 'backup_' . fake()->unique()->word() . '.zip',
            'file_size' => fake()->numberBetween(1000, 10000000),
            'checksum' => hash('sha256', fake()->uuid()),
            'encrypted' => fake()->boolean(),
            'password_hash' => fake()->optional()->password(),
            'metadata' => null,
            'user_id' => User::factory(),
            'started_at' => fake()->optional()->dateTime(),
            'completed_at' => fake()->optional()->dateTime(),
            'error_message' => null,
        ];
    }
}