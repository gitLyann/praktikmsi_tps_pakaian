<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestockRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'employee_id',
        'jumlah_restock',
        'status',
        'catatan',
        'supplier_id',
        'dss_run_id'
    ];

    // Relasi ke Produk
    public function product()
    {
        return $table = $this->belongsTo(Product::class);
    }

    // Relasi ke Employee/Karyawan
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relasi ke pemasok yang dipilih (hasil rekomendasi DSS)
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Relasi ke run analisis DSS yang menjadi dasar pengajuan
    public function dssRun()
    {
        return $this->belongsTo(DssRun::class);
    }
}
