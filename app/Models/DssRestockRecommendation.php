<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DssRestockRecommendation extends Model
{
    use HasFactory;

    protected $table = 'dss_restock_recommendations';

    protected $fillable = [
        'dss_run_id',
        'product_id',
        'supplier_id',
        'current_stock',
        'avg_daily_sales',
        'lead_time_days',
        'safety_stock',
        'reorder_point',
        'recommended_qty',
        'estimated_cost',
        'status',
        'catatan',
    ];

    protected $casts = [
        'avg_daily_sales' => 'float',
        'estimated_cost' => 'float',
        'current_stock' => 'integer',
        'lead_time_days' => 'integer',
        'safety_stock' => 'integer',
        'reorder_point' => 'integer',
        'recommended_qty' => 'integer',
    ];

    // Relasi ke run analisis asal
    public function dssRun()
    {
        return $this->belongsTo(DssRun::class);
    }

    // Relasi ke produk yang direkomendasikan
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relasi ke pemasok pilihan
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Stok saat ini sudah di bawah reorder point -> perlu dipesan
    public function needsRestock(): bool
    {
        return $this->current_stock <= $this->reorder_point;
    }
}
