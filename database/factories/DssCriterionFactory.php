<?php

namespace Database\Factories;

use App\Models\DssCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DssCriterion>
 */
class DssCriterionFactory extends Factory
{
    protected $model = DssCriterion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('CRIT_???')),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['benefit', 'cost']),
            'weight' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * Kriteria benefit: nilai lebih besar lebih baik.
     */
    public function benefit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'benefit',
        ]);
    }

    /**
     * Kriteria cost: nilai lebih kecil lebih baik.
     */
    public function cost(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cost',
        ]);
    }
}
