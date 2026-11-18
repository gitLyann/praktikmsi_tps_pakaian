<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffActivityLog extends Model
{
    /** @use HasFactory<\Database\Factories\StaffActivityLogFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'activity_type',
        'reference_type',
        'reference_id',
        'occurred_at',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
