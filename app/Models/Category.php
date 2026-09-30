<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Menghubungkan ke tabel 'categories' yang sudah ada di phpMyAdmin
    protected $table = 'categories';

    protected $fillable = [
        'name'
    ];

    // Relasi ke Produk
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
