<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Database\Seeder;

/**
 * Mengisi tabel transaction_details untuk transaksi lama (ID 1-6) yang
 * sebelumnya hanya punya baris di tabel transactions, sehingga KIS "Produk
 * Terjual" dan grafik "Top 5 Produk Terlaris" di dashboard MIS punya data.
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=TransactionDetailSeeder
 *
 * Seeder ini idempoten: transaksi yang sudah punya detail akan dilewati,
 * jadi aman dijalankan berulang tanpa menggandakan baris.
 */
class TransactionDetailSeeder extends Seeder
{
    /**
     * Pemetaan transaksi -> item belanja [product_id, quantity].
     * Jumlah (quantity * harga produk) sengaja dibuat sama dengan kolom
     * total di tabel transactions:
     *
     *  - Trx 1: Rp 300.000 = 2 x Kemeja Hitam Polos (Rp 150.000)
     *  - Trx 2: Rp 150.000 = 1 x Kemeja Hitam Polos
     *  - Trx 3: Rp 150.000 = 1 x Kemeja Hitam Polos
     *  - Trx 4: Rp 150.000 = 1 x Kemeja Hitam Polos
     *  - Trx 5: Rp 140.000 = 1 x Jeans Modern (Rp 90.000) + 1 x Gaul Totebag (Rp 50.000)
     *  - Trx 6: Rp  90.000 = 1 x Jeans Modern
     *
     * Subtotal tiap baris dihitung ulang dari harga produk di database
     * (bukan angka mati) supaya tetap konsisten bila harga berubah.
     */
    private const MAPPING = [
        1 => [[3, 2]],
        2 => [[3, 1]],
        3 => [[3, 1]],
        4 => [[3, 1]],
        5 => [[4, 1], [5, 1]],
        6 => [[4, 1]],
    ];

    public function run(): void
    {
        $produk = Product::whereIn('id', [3, 4, 5, 6])->get()->keyBy('id');

        foreach (self::MAPPING as $transactionId => $items) {
            $transaction = Transaction::find($transactionId);

            if (! $transaction) {
                $this->command?->warn("Transaksi ID {$transactionId} tidak ditemukan, dilewati.");
                continue;
            }

            // Idempoten: jangan gandakan detail yang sudah ada.
            if (TransactionDetail::where('transaction_id', $transactionId)->exists()) {
                $this->command?->info("Transaksi ID {$transactionId} sudah punya detail, dilewati.");
                continue;
            }

            $jumlahItem = 0;

            foreach ($items as [$productId, $quantity]) {
                $product = $produk->get($productId);

                if (! $product) {
                    $this->command?->warn("Produk ID {$productId} tidak ditemukan, baris untuk transaksi {$transactionId} dilewati.");
                    continue;
                }

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                    'created_at' => $transaction->date,
                    'updated_at' => $transaction->date,
                ]);

                $jumlahItem += $product->price * $quantity;
            }

            if ($jumlahItem === (float) $transaction->total) {
                $this->command?->info("Transaksi ID {$transactionId}: detail cocok dengan total (Rp " . number_format($jumlahItem, 0, ',', '.') . ").");
            } else {
                $this->command?->warn(
                    "Transaksi ID {$transactionId}: total detail Rp " . number_format($jumlahItem, 0, ',', '.')
                    . ' berbeda dari transactions.total Rp ' . number_format((float) $transaction->total, 0, ',', '.')
                );
            }
        }
    }
}
