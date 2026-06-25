<?php

namespace Database\Factories\Operacional;

use App\Models\Operacional\CashMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    public function definition(): array
    {
        return [
            'cash_register_id' => CashRegisterFactory::new(),
            'type' => fake()->randomElement(['credit', 'debit']),
            'amount' => fake()->randomFloat(2, 1, 10000),
            'description' => fake()->sentence(),
            'document' => fake()->optional()->numerify('DOC-####'),
            'category' => fake()->randomElement(['venda', 'despesa', 'troco', 'sangria', 'reforco', 'outros']),
            'payment_method' => fake()->randomElement(['dinheiro', 'pix', 'boleto', 'cheque', 'cartao']),
            'supplier_id' => null,
            'payable_id' => null,
            'receivable_id' => null,
            'created_by' => null,
        ];
    }
}
