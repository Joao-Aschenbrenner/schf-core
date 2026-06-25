<?php

namespace Database\Factories;

use App\Models\Payable;
use App\Models\Supplier;
use App\Models\HealthPlan;
use App\Models\ExpenseCategory;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayableFactory extends Factory
{
    protected $model = Payable::class;

    public function definition(): array
    {
        $statuses = ['draft', 'pending', 'scheduled', 'paid', 'cancelled', 'overdue'];
        $methods = ['boleto', 'transfer', 'pix', 'check', 'cash', 'deduction', 'other'];

        return [
            'description' => fake()->sentence(),
            'document_number' => fake()->numerify('NF-####'),
            'supplier_id' => Supplier::factory(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'discount' => 0,
            'interest' => 0,
            'paid_amount' => 0,
            'due_date' => fake()->dateTimeBetween('-30 days', '+60 days')->format('Y-m-d'),
            'status' => fake()->randomElement($statuses),
            'payment_method' => fake()->randomElement($methods),
        ];
    }
}
