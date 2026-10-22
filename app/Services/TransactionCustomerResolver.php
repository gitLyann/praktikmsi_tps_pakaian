<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Pemetaan user ke id yang tersimpan di `transactions.customer_id`.
 *
 * Migration asli (`create_transactions_table`) membuat kolom itu menunjuk
 * `customers.id`, sedangkan migrasi `2026_09_17_155756` mengarahkan kolom
 * tersebut ke `users.id` pada MySQL. Karena keduanya masih hidup di repo,
 * penulisan dan pembacaan transaksi harus menyesuaikan skema yang benar-benar
 * dipakai database aktif.
 *
 * Semua pemanggilan memakai user yang sedang login, tidak pernah menerima
 * id user dari request.
 */
class TransactionCustomerResolver
{
    /**
     * Id yang harus dipakai saat menulis `transactions.customer_id` untuk user ini.
     */
    public function customerIdFor(User $user): int
    {
        if ($this->customerForeignKeyTable() === 'users') {
            return (int) $user->id;
        }

        // Skema `customers.id`: pastikan baris customers milik user ini ada.
        $existing = DB::table('customers')->where('user_id', $user->id)->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        DB::table('customers')->insert([
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => null,
            'address' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('customers')->where('user_id', $user->id)->value('id');
    }

    /**
     * Semua id yang mungkin dipakai untuk menyimpan transaksi milik user ini.
     *
     * Dipakai saat membaca transaksi supaya aman pada kedua skema.
     *
     * @return array<int, int>
     */
    public function customerIdsFor(User $user): array
    {
        $ids = DB::table('customers')
            ->where('customers.user_id', $user->id)
            ->pluck('customers.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! in_array((int) $user->id, $ids, true)) {
            $ids[] = (int) $user->id;
        }

        return $ids;
    }

    /**
     * Tabel yang ditunjuk foreign key `transactions.customer_id`.
     */
    public function customerForeignKeyTable(): string
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
