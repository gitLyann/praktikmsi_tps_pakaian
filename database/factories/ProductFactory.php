<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->unique()->words(3, true),
            'type' => fake()->randomElement(['Kaos', 'Kemeja', 'Celana', 'Jaket', 'Hoodie']),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'color' => fake()->randomElement(['Hitam', 'Navy', 'Merah', 'Putih', 'Abu-abu']),
            'price' => fake()->randomFloat(2, 25000, 450000),
            'stock' => fake()->numberBetween(0, 120),
        ];
    }

    /**
     * Produk dengan stok menipis (dipakai skenario restock).
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => fake()->numberBetween(0, 4),
        ]);
    }
}
