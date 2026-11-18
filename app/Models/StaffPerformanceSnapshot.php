<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPerformanceSnapshot extends Model
{
    /** @use HasFactory<\Database\Factories\StaffPerformanceSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period',
        'transactions_count',
        'total_sales',
        'avg_order_value',
        'restock_submitted',
        'restock_approved',
        'evaluations_count',
        'avg_response_hours',
        'target_achievement',
        'performance_score',
        'rank',
        'status',
    ];

    protected $casts = [
        'transactions_count' => 'integer',
        'total_sales' => 'float',
        'avg_order_value' => 'float',
        'restock_submitted' => 'integer',
        'restock_approved' => 'integer',
        'evaluations_count' => 'integer',
        'avg_response_hours' => 'float',
        'target_achievement' => 'float',
        'performance_score' => 'float',
        'rank' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
