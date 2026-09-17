<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    // Menghubungkan ke tabel 'employees' yang sudah ada di phpMyAdmin
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'name',
        'position'
    ];
}
