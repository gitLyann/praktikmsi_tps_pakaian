<?php

namespace Database\Factories;

use App\Models\EsKpiTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EsKpiTarget>
 */
class EsKpiTargetFactory extends Factory
{
    protected $model = EsKpiTarget::class;

    public function definition(): array
    {
        return [
            'period' => fake()->unique()->date('Y-m'),
            'revenue_target' => fake()->randomFloat(2, 1000000, 50000000),
            'created_by' => User::factory(),
        ];
    }
}
