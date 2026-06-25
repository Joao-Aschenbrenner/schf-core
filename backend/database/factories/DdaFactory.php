<?php

namespace Database\Factories;

use App\Models\Dda;
use App\Models\Supplier;
use App\Models\Nfe;
use App\Models\Payable;
use Illuminate\Database\Eloquent\Factories\Factory;

class DdaFactory extends Factory
{
    protected $model = Dda::class;

    public function definition(): array
    {
        $statuses = ['imported', 'identified', 'linked', 'rejected', 'expired'];

        return [
            'bank_code' => fake()->numerify('###'),
            'bank_name' => fake()->randomElement(['Banco do Brasil', 'Itaú', 'Bradesco', 'Caixa']),
            'document_number' => fake()->numerify('########'),
            'title_number' => fake()->numerify('########'),
            'bar_code' => fake()->numerify('###############'),
            'payment_line_code' => fake()->numerify('###############'),
            'payer_name' => fake()->name(),
            'payer_cnpj' => fake()->numerify('##############'),
            'amount' => fake()->randomFloat(2, 10, 50000),
            'due_date' => fake()->dateTimeBetween('-30 days', '+60 days'),
            'supplier_id' => Supplier::factory(),
            'status' => fake()->randomElement($statuses),
            'imported_at' => now(),
        ];
    }
}
