<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengunjung_melihat_seksi_produk_terlaris(): void
    {
        $product = Product::factory()->create(['name' => 'Kemeja Flanel', 'stock' => 12]);
        $this->buatTransaksiSelesai($product, 7);

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Produk Terlaris');
        $response->assertSee('Kemeja Flanel');
        $response->assertSee('pcs terjual');
        $response->assertDontSee('Rekomendasi untukmu');
    }

    public function test_produk_habis_stok_tidak_ditampilkan_sebagai_rekomendasi(): void
    {
        $habis = Product::factory()->create(['name' => 'Hoodie Habis', 'stock' => 0]);
        $tersedia = Product::factory()->create(['name' => 'Celana Tersedia', 'stock' => 5]);
        $this->buatTransaksiSelesai($habis, 3);
        $this->buatTransaksiSelesai($tersedia, 2);

        $response = $this->get(route('landing'));

        $response->assertOk();

        // Katalog tetap menampilkan semua produk, jadi yang diperiksa hanya seksi terlaris.
        $this->assertStringContainsString('Celana Tersedia', $this->sectionTerlaris($response->getContent()));
        $this->assertStringNotContainsString('Hoodie Habis', $this->sectionTerlaris($response->getContent()));
    }

    public function test_pelanggan_login_melihat_rekomendasi_personal(): void
    {
        Product::factory()->create(['name' => 'Jaket Kanvas', 'stock' => 7]);
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $response = $this->actingAs($pelanggan)->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Rekomendasi untukmu');
        $response->assertSee('Rekomendasi personal');
        $response->assertSee('Jaket Kanvas');
    }

    public function test_pelanggan_login_tetap_melihat_produk_terlaris(): void
    {
        $product = Product::factory()->create(['name' => 'Kaos Oversize', 'stock' => 4]);
        $this->buatTransaksiSelesai($product, 5);
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $response = $this->actingAs($pelanggan)->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Produk Terlaris');
        $response->assertSee('Kaos Oversize');
    }

    public function test_staff_melihat_rekomendasi_umum_bukan_personal(): void
    {
        Product::factory()->create(['name' => 'Kaos Polos', 'stock' => 9]);
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('landing'))
            ->assertOk()
            ->assertSee('Produk Terlaris')
            ->assertSee('Rekomendasi populer')
            ->assertDontSee('Rekomendasi personal');
    }

    /**
     * Membuat transaksi selesai beserta detailnya supaya produk terhitung terjual.
     */
    private function buatTransaksiSelesai(Product $product, int $quantity): void
    {
        $transaction = Transaction::create([
            'date' => now()->subDays(2),
            'total' => $product->price * $quantity,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'subtotal' => $product->price * $quantity,
        ]);
    }

    /**
     * Mengambil bagian HTML seksi produk terlaris saja.
     */
    private function sectionTerlaris(string $html): string
    {
        preg_match('/<section id="terlaris".*?<\/section>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
