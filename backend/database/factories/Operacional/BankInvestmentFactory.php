<?php

namespace Database\Factories\Operacional;

use App\Models\Operacional\BankInvestment;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankInvestmentFactory extends Factory
{
    protected $model = BankInvestment::class;

    public function definition(): array
    {
        return [
            'bank_account_id' => \Database\Factories\BankAccountFactory::new(),
            'description' => fake()->words(3, true),
            'investment_type' => fake()->randomElement(['apl', 'aplicacao', 'investimento', 'cdb', 'lci_lca']),
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'yield_rate' => fake()->randomFloat(4, 0.5, 20),
            'start_date' => fake()->date(),
            'maturity_date' => fake()->dateTimeBetween('+30 days', '+365 days')->format('Y-m-d'),
            'status' => 'active',
            'redeemed_amount' => 0,
            'redeemed_at' => null,
            'legacy_conta_id' => null,
            'notes' => null,
        ];
    }
}
