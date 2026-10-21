<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DssRun extends Model
{
    use HasFactory;

    protected $table = 'dss_runs';

    protected $fillable = [
        'name',
        'type',
        'status',
        'created_by',
        'criteria_count',
        'lambda_max',
        'ci',
        'cr',
        'ri',
        'is_consistent',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'lambda_max' => 'float',
        'ci' => 'float',
        'cr' => 'float',
        'ri' => 'float',
        'is_consistent' => 'boolean',
        'criteria_count' => 'integer',
    ];

    // Relasi ke user yang menjalankan analisis
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke skor penilaian pemasok pada run ini
    public function scores()
    {
        return $this->hasMany(DssSupplierScore::class);
    }

    // Relasi ke rekomendasi restock hasil run ini
    public function restockRecommendations()
    {
        return $this->hasMany(DssRestockRecommendation::class);
    }

    // Relasi ke pengajuan restock yang memakai run ini sebagai dasar
    public function restockRequests()
    {
        return $this->hasMany(RestockRequest::class);
    }

    // Label jenis analisis yang enak dibaca
    public function typeLabel(): string
    {
        return match ($this->type) {
            'ahp' => 'AHP',
            'vikor' => 'VIKOR',
            'restock' => 'Rekomendasi Restock',
            default => strtoupper((string) $this->type),
        };
    }
}
