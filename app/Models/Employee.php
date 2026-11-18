<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    // Menghubungkan ke tabel 'employees' yang sudah ada di phpMyAdmin
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'name',
        'position',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restockRequests(): HasMany
    {
        return $this->hasMany(RestockRequest::class);
    }
}
