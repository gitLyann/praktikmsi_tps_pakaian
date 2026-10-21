<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSupplier>
 */
class ProductSupplierFactory extends Factory
{
    protected $model = ProductSupplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'supplier_id' => Supplier::factory(),
            'price' => fake()->randomFloat(2, 25000, 450000),
            'min_order_qty' => fake()->randomElement([10, 20, 25, 50]),
            'lead_time_days' => fake()->numberBetween(3, 21),
            'is_preferred' => false,
            'notes' => null,
        ];
    }
}
