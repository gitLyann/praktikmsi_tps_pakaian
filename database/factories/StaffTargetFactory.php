<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\StaffTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffTarget>
 */
class StaffTargetFactory extends Factory
{
    protected $model = StaffTarget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period' => now()->format('Y-m'),
            'sales_target' => 50000000,
            'transaction_target' => 30,
        ];
    }
}
