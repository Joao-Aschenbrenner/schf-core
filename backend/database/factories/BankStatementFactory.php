<?php

namespace Database\Factories;

use App\Models\BankStatement;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankStatementFactory extends Factory
{
    protected $model = BankStatement::class;

    public function definition(): array
    {
        $statuses = ['imported', 'reconciled', 'closed'];

        return [
            'bank_account_id' => BankAccount::factory(),
            'statement_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'source_file' => fake()->word() . '.ofx',
            'source_type' => fake()->randomElement(['ofx', 'csv', 'manual']),
            'opening_balance' => fake()->randomFloat(2, -10000, 100000),
            'closing_balance' => fake()->randomFloat(2, -10000, 100000),
            'status' => fake()->randomElement($statuses),
        ];
    }
}
