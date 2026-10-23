<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detail pembayaran per transaksi (SIMULASI).
 *
 * Menyimpan kode metode + kode referensi (Virtual Account / kode gerai) dan
 * hanya 4 digit terakhir nomor HP untuk e-money. Tidak pernah menyimpan nomor
 * kartu, CVV, nomor rekening, atau nomor HP lengkap.
 */
class TransactionPayment extends Model
{
    use HasFactory;

    protected $table = 'transaction_payments';

    protected $fillable = [
        'transaction_id',
        'method_code',
        'reference_code',
        'phone_last4',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return PaymentMethod::labelFor($this->method_code);
    }
}
