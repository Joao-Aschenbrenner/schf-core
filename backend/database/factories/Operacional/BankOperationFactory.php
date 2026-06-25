<?php

namespace Database\Factories\Operacional;

use App\Models\Operacional\BankOperation;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankOperationFactory extends Factory
{
    protected $model = BankOperation::class;

    public function definition(): array
    {
        return [
            'bank_account_id' => \Database\Factories\BankAccountFactory::new(),
            'type' => fake()->randomElement(['credit', 'debit', 'investment', 'transfer']),
            'amount' => fake()->randomFloat(2, 1, 100000),
            'description' => fake()->sentence(),
            'document' => fake()->optional()->numerify('DOC-####'),
            'operation_date' => fake()->date(),
            'reference_id' => null,
            'reference_type' => null,
            'payable_id' => null,
            'receivable_id' => null,
            'bank_investment_id' => null,
            'created_by' => null,
        ];
    }
}
