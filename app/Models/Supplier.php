<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'code',
        'contact_name',
        'phone',
        'email',
        'address',
        'city',
        'supplied_categories',
        'lead_time_days',
        'min_order_qty',
        'rating',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'rating' => 'float',
        'is_active' => 'boolean',
        'lead_time_days' => 'integer',
        'min_order_qty' => 'integer',
    ];

    // Relasi ke penawaran produk milik pemasok ini
    public function productSuppliers()
    {
        return $this->hasMany(ProductSupplier::class);
    }

    // Relasi ke produk yang bisa dilayani pemasok ini
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_suppliers');
    }

    // Relasi ke skor penilaian VIKOR
    public function scores()
    {
        return $this->hasMany(DssSupplierScore::class);
    }

    // Relasi ke rekomendasi restock yang memakai pemasok ini
    public function restockRecommendations()
    {
        return $this->hasMany(DssRestockRecommendation::class);
    }

    /**
     * Daftar kategori bahan/produk yang dipasok.
     *
     * Kolomnya disimpan sebagai teks dipisah koma, jadi dipecah di sini
     * supaya bisa langsung dipakai di filter dan tampilan.
     *
     * @return array<int, string>
     */
    public function suppliedCategoryList(): array
    {
        return collect(explode(',', (string) $this->supplied_categories))
            ->map(fn ($category) => trim($category))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
