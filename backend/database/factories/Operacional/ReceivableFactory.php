<?php

namespace Database\Factories\Operacional;

use App\Models\Operacional\Receivable;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceivableFactory extends Factory
{
    protected $model = Receivable::class;

    public function definition(): array
    {
        return [
            'supplier_id' => null,
            'bank_account_id' => null,
            'description' => fake()->sentence(),
            'document_number' => fake()->numerify('DOC-####'),
            'amount' => fake()->randomFloat(2, 100, 100000),
            'discount' => 0,
            'interest' => 0,
            'received_amount' => 0,
            'due_date' => fake()->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'receipt_date' => null,
            'status' => 'pending',
            'payment_method' => fake()->randomElement(['pix', 'boleto', 'transfer', 'check', 'cash']),
            'notes' => null,
            'legacy_nota_id' => null,
            'created_by' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
