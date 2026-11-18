<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTarget extends Model
{
    /** @use HasFactory<\Database\Factories\StaffTargetFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period',
        'sales_target',
        'transaction_target',
    ];

    protected $casts = [
        'sales_target' => 'float',
        'transaction_target' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
