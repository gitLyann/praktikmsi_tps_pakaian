<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Menghubungkan ke tabel 'products' yang sudah ada di phpMyAdmin
    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'type',
        'size',
        'color',
        'image',
        'price',
        'stock'
    ];

    // Relasi ke Kategori
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Path gambar relatif terhadap folder public, atau null bila produk belum punya foto
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }
}
