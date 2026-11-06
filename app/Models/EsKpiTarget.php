<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsKpiTarget extends Model
{
    use HasFactory;

    protected $table = 'es_kpi_targets';

    protected $fillable = [
        'period',
        'revenue_target',
        'created_by',
    ];

    protected $casts = [
        'revenue_target' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
