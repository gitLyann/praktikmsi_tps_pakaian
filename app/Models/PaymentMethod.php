<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Daftar metode pembayaran (SIMULASI, bukan integrasi payment gateway).
 *
 * Dikelompokkan lewat `group_code` supaya modal pembayaran bisa menampilkan
 * daftar lipat (accordion) dengan satu kategori terbuka setiap saat.
 */
class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected $fillable = [
        'group_code',
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Metode yang boleh dipakai pelanggan saat konfirmasi pembelian. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Semua metode aktif, dikelompokkan per `group_code`, terurut. */
    public static function groupedActive(): array
    {
        return static::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('group_code')
            ->all();
    }

    /**
     * Label human-readable untuk kode metode. Dipakai sebagai fallback untuk
     * nilai `transactions.payment_method` lama (E-Wallet, M-Banking, dll.)
     * yang tidak ada di tabel.
     */
    public static function labelFor(?string $code): string
    {
        if (! $code) {
            return '-';
        }

        $label = static::query()->where('code', $code)->value('name');

        if ($label) {
            return $label;
        }

        return static::legacyLabels()[$code] ?? $code;
    }

    /**
     * Label untuk nilai lama yang tersimpan sebelum tabel metode ada.
     *
     * @return array<string, string>
     */
    public static function legacyLabels(): array
    {
        return [
            'E-Wallet' => 'E-Wallet',
            'M-Banking' => 'M-Banking',
            'Transfer Bank' => 'Transfer Bank',
            'Cash' => 'Cash',
            'online' => 'Online',
            'transfer' => 'Transfer Bank',
            'ewallet' => 'E-Wallet',
            'cash' => 'Cash',
        ];
    }
}
