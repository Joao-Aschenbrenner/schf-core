<?php

namespace Database\Factories;

use App\Models\PreLaunch;
use App\Models\Supplier;
use App\Models\HealthPlan;
use App\Models\ResourcePlan;
use App\Models\ExpenseCategory;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreLaunchFactory extends Factory
{
    protected $model = PreLaunch::class;

    public function definition(): array
    {
        $types = ['payroll', 'medical_fees', 'boleto', 'supplier', 'tax', 'other'];
        $statuses = ['projected', 'confirmed', 'converted', 'cancelled'];

        return [
            'description' => fake()->sentence(),
            'type' => fake()->randomElement($types),
            'supplier_id' => Supplier::factory(),
            'estimated_amount' => fake()->randomFloat(2, 100, 50000),
            'actual_amount' => null,
            'expected_date' => fake()->dateTimeBetween('-30 days', '+60 days'),
            'actual_date' => null,
            'status' => fake()->randomElement($statuses),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'projected']);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }
}
