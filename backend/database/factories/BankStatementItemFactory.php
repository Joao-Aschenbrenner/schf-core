<?php

namespace Database\Factories;

use App\Models\BankStatementItem;
use App\Models\BankStatement;
use App\Models\Payable;
use App\Models\PreLaunch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankStatementItemFactory extends Factory
{
    protected $model = BankStatementItem::class;

    public function definition(): array
    {
        return [
            'bank_statement_id' => BankStatement::factory(),
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'description' => fake()->sentence(),
            'document_id' => fake()->optional()->numerify('########'),
            'type' => fake()->randomElement(['debit', 'credit']),
            'amount' => fake()->randomFloat(2, 10, 50000),
            'balance_after' => fake()->randomFloat(2, -10000, 100000),
            'is_reconciled' => false,
        ];
    }

    public function reconciled(): static
    {
        return $this->state(fn () => [
            'is_reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => User::factory(),
        ]);
    }
}
