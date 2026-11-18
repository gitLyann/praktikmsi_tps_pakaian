<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\StaffActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffActivityLog>
 */
class StaffActivityLogFactory extends Factory
{
    protected $model = StaffActivityLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'activity_type' => fake()->randomElement([
                'transaction_processed',
                'restock_requested',
                'restock_decided',
                'supplier_evaluated',
            ]),
            'reference_type' => 'transaction',
            'reference_id' => fake()->numberBetween(1, 1000),
            'occurred_at' => now(),
            'meta' => null,
        ];
    }
}
