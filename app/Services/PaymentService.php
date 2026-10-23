<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Logika pembayaran SIMULASI (tanpa payment gateway).
 *
 * Menentukan metadata tampilan per kelompok metode dan membuat kode referensi
 * (Virtual Account acak per bank / kode gerai acak) beserta batas waktu 24 jam
 * untuk metode yang memang butuh kode. Tidak pernah menyimpan data sensitif:
 * nomor kartu, CVV, nomor rekening, dan nomor HP lengkap tidak disentuh.
 */
class PaymentService
{
    public const GRUP_KARTU = 'card';
    public const GRUP_ATM = 'atm';
    public const GRUP_EMONEY = 'emoney';
    public const GRUP_DIRECT_DEBIT = 'direct_debit';
    public const GRUP_GERAI = 'gerai';
    public const GRUP_TUNAI = 'tunai';

    /**
     * Metadata tampilan per kelompok: label kategori, chip merek, jenis form,
     * dan petunjuk singkat.
     *
     * @return array<int, array<string, mixed>>
     */
    public function groups(): array
    {
        $urutan = [
            self::GRUP_KARTU,
            self::GRUP_ATM,
            self::GRUP_EMONEY,
            self::GRUP_DIRECT_DEBIT,
            self::GRUP_GERAI,
            self::GRUP_TUNAI,
        ];

        $meta = [
            self::GRUP_KARTU => [
                'title' => 'Kartu Debit/Kredit',
                'desc' => 'Pilih jenis kartu lalu konfirmasi. Data kartu tidak disimpan.',
                'brands' => ['VISA', 'Mastercard', 'JCB'],
                'form' => 'card',
            ],
            self::GRUP_ATM => [
                'title' => 'ATM / Transfer Bank',
                'desc' => 'Nomor Virtual Account dan batas bayar 24 jam dibuat otomatis.',
                'brands' => [],
                'form' => 'none',
            ],
            self::GRUP_EMONEY => [
                'title' => 'E-Money',
                'desc' => 'Setujui pembayaran di aplikasi setelah konfirmasi.',
                'brands' => [],
                'form' => 'phone',
            ],
            self::GRUP_DIRECT_DEBIT => [
                'title' => 'Direct Debit',
                'desc' => 'Masukkan nomor rekening (hanya tampilan, tidak dikirim).',
                'brands' => [],
                'form' => 'rekening',
            ],
            self::GRUP_GERAI => [
                'title' => 'Bayar di Gerai',
                'desc' => 'Kode pembayaran dan batas bayar 24 jam dibuat otomatis.',
                'brands' => [],
                'form' => 'none',
            ],
            self::GRUP_TUNAI => [
                'title' => 'Tunai',
                'desc' => 'Bayar saat barang diterima (COD). Tanpa kode.',
                'brands' => [],
                'form' => 'none',
            ],
        ];

        $grouped = PaymentMethod::groupedActive();

        $result = [];
        foreach ($urutan as $code) {
            $items = collect($grouped[$code] ?? [])->map(fn (PaymentMethod $m) => [
                'code' => $m->code,
                'name' => $m->name,
            ])->values()->all();

            if (empty($items)) {
                continue;
            }

            $result[] = [
                'group_code' => $code,
                'title' => $meta[$code]['title'] ?? $code,
                'desc' => $meta[$code]['desc'] ?? '',
                'brands' => $meta[$code]['brands'] ?? [],
                'form' => $meta[$code]['form'] ?? 'none',
                'items' => $items,
            ];
        }

        return $result;
    }

    /**
     * Membuat detail pembayaran untuk transaksi. Mengembalikan null bila kode
     * metode tidak ada / tidak aktif (ditolak server).
     */
    public function createFor(Transaction $transaction, string $code, ?string $phone = null): ?TransactionPayment
    {
        $method = PaymentMethod::query()->active()->where('code', $code)->first();

        if (! $method) {
            return null;
        }

        $reference = null;
        $expiresAt = null;
        $phoneLast4 = null;

        if ($method->group_code === self::GRUP_ATM) {
            $reference = $this->generateVirtualAccount($method->code);
            $expiresAt = now()->addHours(24);
        } elseif ($method->group_code === self::GRUP_GERAI) {
            $reference = $this->generateVoucherCode();
            $expiresAt = now()->addHours(24);
        } elseif ($method->group_code === self::GRUP_EMONEY) {
            $phoneLast4 = $this->last4($phone);
        }

        return $transaction->payment()->create([
            'method_code' => $method->code,
            'reference_code' => $reference,
            'phone_last4' => $phoneLast4,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);
    }

    /** Awalan Virtual Account berbeda per bank. */
    public function virtualAccountPrefix(string $code): string
    {
        return match ($code) {
            'atm_bca' => '8808',
            'atm_bni' => '8810',
            'atm_bri' => '8820',
            'atm_mandiri' => '8830',
            default => '8899',
        };
    }

    private function generateVirtualAccount(string $code): string
    {
        return $this->virtualAccountPrefix($code) . Str::padLeft((string) random_int(0, 9999999999), 10, '0');
    }

    private function generateVoucherCode(): string
    {
        return 'GERAI-' . Str::upper(Str::random(8));
    }

    /** Hanya 4 digit terakhir nomor HP yang boleh disimpan. */
    private function last4(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) < 4) {
            throw new InvalidArgumentException('Nomor HP tidak valid.');
        }

        return substr($digits, -4);
    }
}
