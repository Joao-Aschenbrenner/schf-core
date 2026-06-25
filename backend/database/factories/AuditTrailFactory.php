<?php

namespace Database\Factories;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditTrailFactory extends Factory
{
    protected $model = AuditTrail::class;

    public function definition(): array
    {
        $actions = ['created', 'updated', 'deleted', 'restored'];

        return [
            'model_type' => fake()->randomElement([
                \App\Models\Payable::class,
                \App\Models\Nfe::class,
                \App\Models\Supplier::class,
            ]),
            'model_id' => fake()->randomNumber(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement($actions),
            'old_values' => null,
            'new_values' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'reason' => fake()->sentence(),
        ];
    }
}
