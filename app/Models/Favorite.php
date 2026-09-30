<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $table = 'favorites';

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    // Relasi ke User/Pelanggan yang memfavoritkan produk
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Produk yang difavoritkan
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
