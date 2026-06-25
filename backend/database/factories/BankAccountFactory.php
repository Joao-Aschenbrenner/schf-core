<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\HealthPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_code' => fake()->numerify('###'),
            'bank_name' => fake()->randomElement(['Banco do Brasil', 'Itaú', 'Bradesco', 'Caixa', 'Santander', 'Nubank']),
            'agency' => fake()->numerify('####'),
            'account' => fake()->numerify('########'),
            'digit' => fake()->numerify('#'),
            'type' => fake()->randomElement(['checking', 'savings', 'investment']),
            'holder_name' => fake()->name(),
            'holder_cnpj' => fake()->numerify('##############'),
            'current_balance' => fake()->randomFloat(2, -10000, 500000),
            'is_active' => true,
        ];
    }

    public function healthPlan(HealthPlan $plan): static
    {
        return $this->state(fn () => ['health_plan_id' => $plan->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
