<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Skor penilaian satu pemasok terhadap satu kriteria pada satu run analisis.
 *
 * `score` menyimpan nilai mentah kriteria (mis. harga beli, lead time, rating).
 * Arah benefit/cost mengikuti `DssCriterion::type` saat perhitungan VIKOR.
 */
class DssSupplierScore extends Model
{
    use HasFactory;

    protected $table = 'dss_supplier_scores';

    protected $fillable = [
        'dss_run_id',
        'supplier_id',
        'dss_criterion_id',
        'score',
        'weight',
    ];

    protected $casts = [
        'score' => 'float',
        'weight' => 'float',
    ];

    // Relasi ke run analisis
    public function dssRun()
    {
        return $this->belongsTo(DssRun::class);
    }

    // Relasi ke pemasok yang dinilai
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Relasi ke kriteria penilaian
    public function criterion()
    {
        return $this->belongsTo(DssCriterion::class, 'dss_criterion_id');
    }
}
