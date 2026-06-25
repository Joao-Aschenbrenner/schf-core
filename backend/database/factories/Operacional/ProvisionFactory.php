<?php

namespace Database\Factories\Operacional;

use App\Models\Operacional\Provision;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProvisionFactory extends Factory
{
    protected $model = Provision::class;

    public function definition(): array
    {
        return [
            'supplier_id' => null,
            'bank_account_id' => null,
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 100000),
            'due_date' => fake()->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'status' => 'draft',
            'provision_type' => fake()->randomElement(['payroll', 'medical_fees', 'tax', 'supplier', 'recurring']),
            'notes' => null,
            'paid_amount' => 0,
            'paid_at' => null,
            'created_by' => null,
            'legacy_nota_id' => null,
        ];
    }
}
