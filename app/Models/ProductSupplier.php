<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSupplier extends Model
{
    use HasFactory;

    protected $table = 'product_suppliers';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'price',
        'min_order_qty',
        'lead_time_days',
        'is_preferred',
        'notes',
    ];

    protected $casts = [
        'price' => 'float',
        'is_preferred' => 'boolean',
        'lead_time_days' => 'integer',
        'min_order_qty' => 'integer',
    ];

    // Relasi ke produk
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relasi ke pemasok
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
