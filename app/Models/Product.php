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
        'price',
        'stock'
    ];
}
