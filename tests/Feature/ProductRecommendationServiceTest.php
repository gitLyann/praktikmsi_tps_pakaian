<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Services\ProductRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductRecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductRecommendationService;
    }

    public function test_rekomendasi_memprioritaskan_produk_kategori_favorit_yang_belum_dibeli(): void
    {
        $user = $this->makeCustomer();
        $kaos = Category::factory()->create(['name' => 'Kaos']);
        $kemeja = Category::factory()->create(['name' => 'Kemeja']);

        $produkBeli = Product::factory()->create(['category_id' => $kaos->id, 'stock' => 10]);
        $produkBaru = Product::factory()->create(['category_id' => $kaos->id, 'stock' => 5]);
        $produkTerlaris = Product::factory()->create(['category_id' => $kemeja->id, 'stock' => 8]);
        $produkLain = Product::factory()->create(['category_id' => $kemeja->id, 'stock' => 3]);

        $this->createSale($user, $produkBeli, quantity: 1);
        $this->createSale($user, $produkTerlaris, quantity: 10);

        $result = $this->service->recommendFor($user, 4);

        $this->assertCount(4, $result);

        $ids = $result->pluck('product.id')->all();

        $this->assertSame($produkBaru->id, $ids[0]);
        $this->assertContains($produkTerlaris->id, $ids);
        $this->assertContains($produkLain->id, $ids);
    }

    public function test_alasan_rekomendasi_menampilkan_kategori_favorit_dan_produk_terlaris(): void
    {
        $user = $this->makeCustomer();
        $kaos = Category::factory()->create(['name' => 'Kaos']);
        $kemeja = Category::factory()->create(['name' => 'Kemeja']);

        $produkBeli = Product::factory()->create(['category_id' => $kaos->id, 'stock' => 10]);
        $produkBaru = Product::factory()->create(['category_id' => $kaos->id, 'stock' => 5]);
        $produkTerlaris = Product::factory()->create(['category_id' => $kemeja->id, 'stock' => 8]);

        $this->createSale($user, $produkBeli, quantity: 1);
        $this->createSale($user, $produkTerlaris, quantity: 10);

        $result = $this->service->recommendFor($user, 4);

        $baru = $result->firstWhere('product.id', $produkBaru->id);
        $terlaris = $result->firstWhere('product.id', $produkTerlaris->id);

        $this->assertNotNull($baru);
        $this->assertContains('Sesuai kategori favorit Anda', $baru['reasons']);
        $this->assertContains('Stok tersedia', $baru['reasons']);

        $this->assertNotNull($terlaris);
        $this->assertContains('Produk terlaris', $terlaris['reasons']);
    }

    /**
     * Skema acuan (migration asli `create_transactions_table`):
     * `transactions.customer_id` -> `customers.id`, `customers.user_id` -> `users.id`.
     */
    public function test_transaksi_dipetakan_lewat_tabel_customers(): void
    {
        $user = $this->makeCustomer();
        $category = Category::factory()->create();

        $produk = Product::factory()->create(['category_id' => $category->id, 'stock' => 6]);

        $this->createSale($user, $produk, quantity: 12);

        $customerId = DB::table('customers')->where('user_id', $user->id)->value('id');

        $this->assertNotNull($customerId);
        $this->assertNotSame($user->id, (int) $customerId, 'customers.id harus berbeda dari users.id pada skema acuan.');

        // Transaksi memakai customers.id sesuai skema acuan.
        $this->assertSame(
            (int) $customerId,
            (int) DB::table('transactions')->where('customer_id', $customerId)->value('customer_id')
        );

        $result = $this->service->recommendFor($user, 4);

        $this->assertContains('Produk terlaris', $result->first()['reasons']);
    }

    /**
     * Skema hasil migrasi `2026_09_17_155756` (MySQL): `transactions.customer_id`
     * menunjuk `users.id` langsung, tanpa baris `customers`.
     */
    public function test_transaksi_dengan_customer_id_user_id_ikut_terdeteksi(): void
    {
        $user = User::factory()->create(['role' => 'pelanggan']);

        // Baris customers dengan id = id user (tanpa user_id) supaya FK SQLite terpenuhi,
        // tetapi user ini tidak memiliki baris customers milikannya sendiri.
        DB::table('customers')->insert([
            'id' => $user->id,
            'user_id' => null,
            'name' => 'Data lama tanpa user_id',
            'phone' => '080000000000',
            'address' => 'Alamat lama',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = Category::factory()->create();
        $produk = Product::factory()->create(['category_id' => $category->id, 'stock' => 6]);

        $this->createSale($user, $produk, quantity: 12);

        $result = $this->service->recommendFor($user, 4);

        $this->assertContains('Produk terlaris', $result->first()['reasons']);
    }

    public function test_produk_habis_stok_tidak_direkomendasikan(): void
    {
        $user = $this->makeCustomer();
        $category = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'stock' => 0]);
        Product::factory()->create(['category_id' => $category->id, 'stock' => 4]);

        $result = $this->service->recommendFor($user, 4);

        $this->assertCount(1, $result);
        $this->assertSame(4, $result->first()['product']->stock);
    }

    public function test_rekomendasi_tanpa_login_menampilkan_produk_terlaris(): void
    {
        $category = Category::factory()->create();

        $produkTerlaris = Product::factory()->create(['category_id' => $category->id, 'stock' => 8]);
        Product::factory()->create(['category_id' => $category->id, 'stock' => 5]);

        $this->createSale(null, $produkTerlaris, quantity: 12);

        $result = $this->service->recommendForGuest(2);

        $this->assertCount(2, $result);
        $this->assertSame($produkTerlaris->id, $result->first()['product']->id);
        $this->assertContains('Produk terlaris', $result->first()['reasons']);
    }

    public function test_pelanggan_tanpa_riwayat_belanja_mendapat_rekomendasi_umum(): void
    {
        $user = $this->makeCustomer();
        $category = Category::factory()->create();

        Product::factory()->create(['category_id' => $category->id, 'stock' => 7]);

        $result = $this->service->recommendFor($user, 4);

        $this->assertCount(1, $result);
        $this->assertSame(['Stok tersedia'], $result->first()['reasons']);
    }

    public function test_transaksi_bukan_completed_diabaikan(): void
    {
        $user = $this->makeCustomer();
        $category = Category::factory()->create();

        $produk = Product::factory()->create(['category_id' => $category->id, 'stock' => 6]);

        $this->createSale($user, $produk, quantity: 30, status: 'cancelled');

        $result = $this->service->recommendFor($user, 4);

        $this->assertNotContains('Produk terlaris', $result->first()['reasons']);
    }

    /**
     * Membuat user + baris `customers` miliknya.
     *
     * `customers.id` sengaja dibuat berbeda dari `users.id` supaya test benar-benar
     * memverifikasi pemetaan lewat `customers.user_id`.
     */
    private function makeCustomer(): User
    {
        $user = User::factory()->create(['role' => 'pelanggan']);

        DB::table('customers')->insert([
            'id' => 900,
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => '080000000000',
            'address' => 'Alamat Uji',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function createSale(?User $user, Product $product, int $quantity, string $status = 'completed'): Transaction
    {
        $customerId = $user === null
            ? null
            : DB::table('customers')->where('user_id', $user->id)->value('id');

        $transaction = Transaction::create([
            'customer_id' => $customerId,
            'date' => now(),
            'total' => $quantity * $product->price,
            'payment_method' => 'cash',
            'status' => $status,
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'subtotal' => $quantity * $product->price,
        ]);

        return $transaction;
    }
}
