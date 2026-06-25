<?php

namespace Database\Factories\Operacional;

use App\Models\Operacional\CashRegister;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashRegisterFactory extends Factory
{
    protected $model = CashRegister::class;

    public function definition(): array
    {
        static $dateSequence = 0;
        $dateSequence++;

        return [
            'register_date' => now()->addDays($dateSequence)->format('Y-m-d'),
            'opening_balance' => fake()->randomFloat(2, 100, 5000),
            'closing_balance' => null,
            'total_credits' => 0,
            'total_debits' => 0,
            'status' => 'open',
            'operator' => fake()->name(),
            'closed_by' => null,
            'closed_at' => null,
            'notes' => null,
        ];
    }
}
