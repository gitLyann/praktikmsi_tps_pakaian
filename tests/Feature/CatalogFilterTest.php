<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Services\TransactionCustomerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filter katalog pelanggan bekerja lewat query string dan diproses server,
 * sehingga hasil bisa dibagikan dan tombol Back browser tetap benar.
 */
class CatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    private function pelanggan(): User
    {
        return User::factory()->create(['role' => 'pelanggan']);
    }

    private function produk(string $nama, Category $kategori, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $kategori->id,
            'name' => $nama,
            'type' => 'Atasan',
            'size' => 'M',
            'color' => 'Hitam',
            'price' => 100000,
            'stock' => 10,
        ], $overrides));
    }

    public function test_filter_kategori_hanya_menampilkan_produk_kategori_itu(): void
    {
        $pelanggan = $this->pelanggan();
        $atasan = Category::create(['name' => 'Atasan']);
        $celana = Category::create(['name' => 'Celana']);

        $this->produk('Kaos Atasan', $atasan);
        $this->produk('Celana Panjang', $celana);

        $html = $this->actingAs($pelanggan)
            ->get(route('toko.index', ['kategori' => ['Celana']]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Celana Panjang', $html);
        $this->assertStringNotContainsString('Kaos Atasan', $html);
    }

    public function test_filter_jenis_ukuran_warna_dan_rentang_harga_berkombinasi(): void
    {
        $pelanggan = $this->pelanggan();
        $kategori = Category::create(['name' => 'Atasan']);

        $cocok = $this->produk('Kemeja Cocok', $kategori, [
            'type' => 'Kemeja', 'size' => 'L', 'color' => 'Navy', 'price' => 150000,
        ]);
        $this->produk('Kemeja Mahal', $kategori, [
            'type' => 'Kemeja', 'size' => 'L', 'color' => 'Navy', 'price' => 500000,
        ]);
        $this->produk('Kaos Salah', $kategori, [
            'type' => 'Kaos', 'size' => 'M', 'color' => 'Merah', 'price' => 150000,
        ]);

        $query = [
            'kategori' => ['Atasan'],
            'jenis' => ['Kemeja'],
            'ukuran' => ['L'],
            'warna' => ['Navy'],
            'min' => 100000,
            'max' => 200000,
        ];

        $response = $this->actingAs($pelanggan)->get(route('toko.index', $query))->assertOk();
        $products = $response->viewData('products');

        $this->assertCount(1, $products);
        $this->assertSame($cocok->id, $products->first()->id);
    }

    public function test_urutan_termurah_dan_termahal(): void
    {
        $pelanggan = $this->pelanggan();
        $kategori = Category::create(['name' => 'Atasan']);

        $this->produk('Produk Murah', $kategori, ['price' => 50000]);
        $this->produk('Produk Sedang', $kategori, ['price' => 100000]);
        $this->produk('Produk Mahal', $kategori, ['price' => 250000]);

        $termurah = $this->actingAs($pelanggan)
            ->get(route('toko.index', ['urut' => 'termurah']))->assertOk()->viewData('products')
            ->pluck('name')->all();
        $this->assertSame(['Produk Murah', 'Produk Sedang', 'Produk Mahal'], $termurah);

        $termahal = $this->actingAs($pelanggan)
            ->get(route('toko.index', ['urut' => 'termahal']))->assertOk()->viewData('products')
            ->pluck('name')->all();
        $this->assertSame(['Produk Mahal', 'Produk Sedang', 'Produk Murah'], $termahal);
    }

    public function test_urutan_terlaris_berdasarkan_jumlah_terjual(): void
    {
        $pelanggan = $this->pelanggan();
        $kategori = Category::create(['name' => 'Atasan']);

        $sedikit = $this->produk('Terjual Sedikit', $kategori);
        $banyak = $this->produk('Terjual Banyak', $kategori);
        $tidakTerjual = $this->produk('Belum Terjual', $kategori);

        $transaksi = Transaction::create([
            'customer_id' => app(TransactionCustomerResolver::class)->customerIdFor($pelanggan),
            'employee_id' => null,
            'date' => now(),
            'total' => 100000,
            'payment_method' => 'cod',
            'status' => 'completed',
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaksi->id, 'product_id' => $sedikit->id, 'quantity' => 1, 'subtotal' => 100000,
        ]);
        TransactionDetail::create([
            'transaction_id' => $transaksi->id, 'product_id' => $banyak->id, 'quantity' => 9, 'subtotal' => 900000,
        ]);

        $urut = $this->actingAs($pelanggan)
            ->get(route('toko.index', ['urut' => 'terlaris']))->assertOk()->viewData('products')
            ->pluck('name')->all();

        $this->assertSame('Terjual Banyak', $urut[0]);
        $this->assertSame('Terjual Sedikit', $urut[1]);
        $this->assertSame('Belum Terjual', $urut[2]);
    }

    public function test_pencarian_kata_kunci_dan_urutan_tidak_valid_diabaikan(): void
    {
        $pelanggan = $this->pelanggan();
        $kategori = Category::create(['name' => 'Atasan']);

        $this->produk('Kemeja Flanel', $kategori);
        $this->produk('Kaos Biasa', $kategori);

        $response = $this->actingAs($pelanggan)
            ->get(route('toko.index', ['q' => 'Flanel', 'urut' => 'ngawur']))
            ->assertOk();

        $this->assertSame(['Kemeja Flanel'], $response->viewData('products')->pluck('name')->all());
        $this->assertSame('terbaru', $response->viewData('filters')['urut']);
    }
}
