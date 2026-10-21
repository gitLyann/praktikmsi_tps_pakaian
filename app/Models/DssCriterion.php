<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DssCriterion extends Model
{
    use HasFactory;

    protected $table = 'dss_criteria';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'weight',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relasi ke perbandingan pairwise yang menyebut kriteria ini sebagai kolom A
    public function comparisonsAsA()
    {
        return $this->hasMany(DssCriterionComparison::class, 'criterion_a_id');
    }

    // Relasi ke perbandingan pairwise yang menyebut kriteria ini sebagai kolom B
    public function comparisonsAsB()
    {
        return $this->hasMany(DssCriterionComparison::class, 'criterion_b_id');
    }

    // Relasi ke skor penilaian pemasok terhadap kriteria ini
    public function scores()
    {
        return $this->hasMany(DssSupplierScore::class);
    }

    // Kriteria benefit: nilai besar = lebih baik. Kriteria cost: nilai kecil = lebih baik.
    public function isBenefit(): bool
    {
        return $this->type === 'benefit';
    }
}
