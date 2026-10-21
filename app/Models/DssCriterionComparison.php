<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DssCriterionComparison extends Model
{
    use HasFactory;

    protected $table = 'dss_criteria_comparisons';

    protected $fillable = [
        'criterion_a_id',
        'criterion_b_id',
        'value',
    ];

    protected $casts = [
        'value' => 'float',
    ];

    // Relasi ke kriteria kolom A
    public function criterionA()
    {
        return $this->belongsTo(DssCriterion::class, 'criterion_a_id');
    }

    // Relasi ke kriteria kolom B
    public function criterionB()
    {
        return $this->belongsTo(DssCriterion::class, 'criterion_b_id');
    }
}
