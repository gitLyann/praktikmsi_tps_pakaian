<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Seeder transaksi demo untuk 30 hari terakhir.
 *
 * Tuannya: mengisi tabel `transactions` + `transaction_details` dengan riwayat
 * pembelian yang realistis supaya:
 * - Rekomendasi restock DSS punya data demand (reorder point bisa dihitung).
 * - Rekomendasi produk pelanggan punya data popularitas 90 hari.
 * - Dashboard MIS (analisis penjualan) punya transaksi pada periode berjalan.
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=DssDemoTransactionSeeder
 *
 * Catatan:
 * - Stok produk **tidak** diubah, karena kolom stok berisi posisi stok terkini.
 * - `transactions.customer_id` diarahkan ke `users.id` di MySQL (migrasi
 *   2026_09_17_155756) tetapi tetap menunjuk `customers.id` di SQLite, jadi
 *   seeder mendeteksi target foreign key lebih dulu.
 * - Idempoten: bila periode 30 hari terakhir sudah berisi transaksi yang cukup
 *   banyak, seeder dilewati supaya data demo tidak digandakan.
 */
class DssDemoTransactionSeeder extends Seeder
{
    /** Rentang riwayat yang dibuat (hari). */
    private const DAYS = 30;

    /** Jumlah transaksi per hari. */
    private const MIN_TRANSACTIONS_PER_DAY = 2;

    private const MAX_TRANSACTIONS_PER_DAY = 4;

    /** Bila transaksi selesai di periode ini sudah mencapai angka ini, seeder dilewati. */
    private const EXISTING_TRANSACTION_THRESHOLD = 30;

    /** Pelanggan demo yang dipakai untuk transaksi. */
    private const CUSTOMERS = [
        ['name' => 'Dewi Anggraini', 'email' => 'dewi.demo@lamamora.test'],
        ['name' => 'Budi Santoso', 'email' => 'budi.demo@lamamora.test'],
        ['name' => 'Siti Rahayu', 'email' => 'siti.demo@lamamora.test'],
        ['name' => 'Andi Wijaya', 'email' => 'andi.demo@lamamora.test'],
    ];

    private const PAYMENT_METHODS = ['Transfer BCA', 'E-Wallet', 'COD'];

    /** Two produk pertama dianggap paling laris supaya ada demand tinggi. */
    private const POPULAR_PRODUCT_COUNT = 2;

    public function run(): void
    {
        $products = Product::orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->command?->warn('Belum ada produk, transaksi demo dilewati.');

            return;
        }

        $existing = Transaction::query()
            ->where('status', 'completed')
            ->where('date', '>=', now()->subDays(self::DAYS)->startOfDay())
            ->count();

        if ($existing >= self::EXISTING_TRANSACTION_THRESHOLD) {
            $this->command?->info(
                'Sudah ada '.$existing.' transaksi selesai dalam '.self::DAYS.' hari terakhir, seeder dilewati.'
            );

            return;
        }

        $customerIds = $this->customerIdsFor($this->ensureCustomers());

        if ($customerIds === []) {
            $this->command?->warn('Pelanggan demo gagal disiapkan, transaksi demo dilewati.');

            return;
        }

        // Dua produk dengan id terkecil dianggap paling laris supaya ada demand tinggi.
        $popularIds = $products->keys()->sort()->values()->take(self::POPULAR_PRODUCT_COUNT)->all();

        mt_srand(20261021);

        $created = 0;

        for ($day = self::DAYS - 1; $day >= 0; $day--) {
            $date = now()
                ->subDays($day)
                ->setTime(random_int(9, 19), random_int(0, 59), 0);

            $transactionCount = random_int(self::MIN_TRANSACTIONS_PER_DAY, self::MAX_TRANSACTIONS_PER_DAY);

            for ($i = 0; $i < $transactionCount; $i++) {
                $this->createTransaction($products, $popularIds, $customerIds, $date->copy()->addMinutes($i));
                $created++;
            }
        }

        $this->command?->info('Transaksi demo dibuat: '.$created.' transaksi dalam '.self::DAYS.' hari terakhir.');
    }

    /**
     * Membuat satu transaksi beserta detail barangnya.
     *
     * @param  Collection<int, Product>  $products
     * @param  array<int, int>  $popularIds
     * @param  array<int, int>  $customerIds
     */
    private function createTransaction(Collection $products, array $popularIds, array $customerIds, \DateTimeInterface $date): void
    {
        // created_at/updated_at tidak termasuk fillable, jadi ditulis tanpa proteksi mass assignment.
        $transaction = Transaction::unguarded(fn (): Transaction => Transaction::create([
            'customer_id' => $customerIds[array_rand($customerIds)],
            'employee_id' => null,
            'date' => $date,
            'total' => 0,
            'payment_method' => self::PAYMENT_METHODS[array_rand(self::PAYMENT_METHODS)],
            'status' => 'completed',
            'created_at' => $date,
            'updated_at' => $date,
        ]));

        $total = 0;
        $detailCount = random_int(1, 3);
        $picked = [];

        while (count($picked) < min($detailCount, $products->count())) {
            $product = $products->random();
            $picked[$product->id] = $product;
        }

        foreach ($picked as $product) {
            $isPopular = in_array((int) $product->id, $popularIds, true);
            $quantity = $isPopular ? random_int(2, 5) : random_int(1, 2);
            $subtotal = round($product->price * $quantity, 2);

            TransactionDetail::unguarded(fn (): TransactionDetail => TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'created_at' => $date,
                'updated_at' => $date,
            ]));

            $total += $subtotal;
        }

        $transaction->update(['total' => $total]);
    }

    /**
     * Menyiapkan akun pelanggan demo.
     *
     * @return Collection<int, User>
     */
    private function ensureCustomers(): Collection
    {
        $customers = collect();

        foreach (self::CUSTOMERS as $data) {
            $user = User::query()->where('email', $data['email'])->first();

            if ($user === null) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => 'password',
                    'role' => 'pelanggan',
                ]);
            } elseif ($user->role !== 'pelanggan') {
                $user->update(['role' => 'pelanggan']);
            }

            $customers->push($user);
        }

        return $customers;
    }

    /**
     * Id pelanggan yang dipakai pada kolom `transactions.customer_id`.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, int>
     */
    private function customerIdsFor(Collection $users): array
    {
        if ($this->customerForeignKeyTable() === 'users') {
            return $users->map(fn (User $user): int => (int) $user->id)->all();
        }

        $ids = [];

        foreach ($users as $user) {
            DB::table('customers')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'phone' => null,
                    'address' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $ids[] = (int) DB::table('customers')->where('user_id', $user->id)->value('id');
        }

        return $ids;
    }

    /**
     * Menentukan tabel yang ditunjuk foreign key `transactions.customer_id`.
     */
    private function customerForeignKeyTable(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA foreign_key_list('transactions')") as $row) {
                if (($row->from ?? null) === 'customer_id') {
                    return (string) $row->table;
                }
            }

            return 'customers';
        }

        $row = DB::selectOne(
            'SELECT REFERENCED_TABLE_NAME AS referenced_table
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            ['transactions', 'customer_id']
        );

        return $row->referenced_table ?? 'customers';
    }
}
