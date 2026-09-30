<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'customer_id',
        'employee_id',
        'date',
        'total',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'date' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Relasi ke detail/barang yang dibeli pada transaksi ini
    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}