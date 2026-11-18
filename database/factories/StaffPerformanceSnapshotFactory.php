<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\StaffPerformanceSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffPerformanceSnapshot>
 */
class StaffPerformanceSnapshotFactory extends Factory
{
    protected $model = StaffPerformanceSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period' => now()->format('Y-m'),
            'transactions_count' => 0,
            'total_sales' => 0,
            'avg_order_value' => 0,
            'restock_submitted' => 0,
            'restock_approved' => 0,
            'evaluations_count' => 0,
            'avg_response_hours' => 0,
            'target_achievement' => 0,
            'performance_score' => null,
            'rank' => null,
            'status' => 'green',
        ];
    }
}
