<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EisException extends Model
{
    /** @use HasFactory<\Database\Factories\EisExceptionFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period',
        'type',
        'severity',
        'message',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
