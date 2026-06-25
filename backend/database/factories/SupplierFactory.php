<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'cnpj' => fake()->unique()->numerify('##############'),
            'trade_name' => fake()->companySuffix(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
