<?php

namespace Database\Factories;

use App\Models\EisException;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EisException>
 */
class EisExceptionFactory extends Factory
{
    protected $model = EisException::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period' => now()->format('Y-m'),
            'type' => 'target_achievement',
            'severity' => 'danger',
            'message' => fake()->sentence(),
            'resolved_at' => null,
        ];
    }
}
