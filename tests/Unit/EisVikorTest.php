<?php

namespace Tests\Unit;

use App\Services\VikorService;
use PHPUnit\Framework\TestCase;

class EisVikorTest extends TestCase
{
    public function test_q_is_sorted_ascending_and_cost_criterion_is_handled(): void
    {
        $service = new VikorService();

        $result = $service->calculate(
            [
                1 => [1 => 100000.0, 2 => 10.0, 3 => 0.5],
                2 => [1 => 50000.0, 2 => 5.0, 3 => 5.0],
                3 => [1 => 20000.0, 2 => 2.0, 3 => 20.0],
            ],
            [1 => 0.35, 2 => 0.20, 3 => 0.15],
            [1 => 'benefit', 2 => 'benefit', 3 => 'cost'],
            0.5
        );

        $this->assertSame(1, $result['best_id']);
        $this->assertSame([1, 2, 3], array_values($result['ranking']));
        $this->assertLessThanOrEqual($result['scores'][2]['Q'], $result['scores'][1]['Q']);
        $this->assertLessThanOrEqual($result['scores'][3]['Q'], $result['scores'][2]['Q']);
        $this->assertSame(1, $result['scores'][1]['rank']);
    }
}
