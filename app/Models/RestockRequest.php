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
        'catatan'
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
}
