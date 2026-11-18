<?php

namespace Database\Factories;

use App\Models\EisCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EisCriterion>
 */
class EisCriterionFactory extends Factory
{
    protected $model = EisCriterion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'eis-'.fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'type' => 'benefit',
            'weight' => 0.2000,
        ];
    }

    public function cost(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'cost']);
    }
}
