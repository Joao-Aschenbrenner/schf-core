<?php

namespace Database\Factories;

use App\Models\Nfe;
use App\Models\Supplier;
use App\Models\HealthPlan;
use App\Models\ResourcePlan;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class NfeFactory extends Factory
{
    protected $model = Nfe::class;

    public function definition(): array
    {
        $statuses = ['pending', 'classified', 'linked', 'cancelled'];

        return [
            'nfe_key' => fake()->numerify('###############'),
            'nfe_number' => fake()->unique()->randomNumber(6),
            'serie' => '1',
            'emission_date' => fake()->dateTimeBetween('-90 days', 'now'),
            'entry_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'supplier_id' => Supplier::factory(),
            'goods_value' => fake()->randomFloat(2, 100, 100000),
            'service_value' => 0,
            'insurance_value' => 0,
            'other_value' => 0,
            'icms_value' => fake()->randomFloat(2, 0, 10000),
            'ipi_value' => 0,
            'pis_value' => fake()->randomFloat(2, 0, 1000),
            'cofins_value' => fake()->randomFloat(2, 0, 1000),
            'total_value' => fake()->randomFloat(2, 100, 100000),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement($statuses),
            'is_manual_entry' => fake()->boolean(10),
        ];
    }
}
