<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Data dasar Decision Support System (pemasok, kriteria AHP, matriks perbandingan).
        $this->call(DssSeeder::class);

        // Riwayat transaksi demo (90 hari, variasi status/kategori/metode) - idempoten.
        $this->call(DssDemoTransactionSeeder::class);

        // Skor penilaian pemasok demo supaya ranking VIKOR punya data.
        $this->call(DssScoreSeeder::class);

        // Daftar metode pembayaran (simulasi) untuk modal pembelian.
        $this->call(PaymentMethodSeeder::class);

        // Kategori dasar katalog (idempoten, tidak menghapus data lama).
        $this->call(CategorySeeder::class);

        // KMS (chatbot, idempoten) dan demo ES/OAS.
        $this->call(ChatbotSeeder::class);
        $this->call(EsDemoRestockSeeder::class);
        $this->call(EsKpiTargetSeeder::class);
    }
}
