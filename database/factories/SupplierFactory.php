<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $leadTime = fake()->numberBetween(3, 21);

        return [
            'name' => fake()->company().' Konveksi',
            'code' => strtoupper(fake()->unique()->bothify('SUP-###')),
            'contact_name' => fake()->name(),
            'phone' => '08'.fake()->numerify('##-####-####'),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->address(),
            'city' => fake()->randomElement(['Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Jakarta']),
            'lead_time_days' => $leadTime,
            'min_order_qty' => fake()->randomElement([10, 20, 25, 50]),
            'rating' => fake()->randomFloat(2, 3, 5),
            'is_active' => true,
            'notes' => null,
        ];
    }

    /**
     * Pemasok dengan lead time panjang (untuk skenario restock kritis).
     */
    public function slowLeadTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_time_days' => fake()->numberBetween(15, 30),
        ]);
    }
}
