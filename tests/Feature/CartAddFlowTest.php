<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur menambah ke keranjang: katalog hanya membuka modal konfirmasi, dan
 * item benar-benar tersimpan hanya setelah POST ke endpoint keranjang.
 */
class CartAddFlowTest extends TestCase
{
    use RefreshDatabase;

    private function pelanggan(): User
    {
        return User::factory()->create(['role' => 'pelanggan']);
    }

    private function produk(int $stok = 10): Product
    {
        $kategori = Category::create(['name' => 'Atasan']);

        return Product::create([
            'category_id' => $kategori->id,
            'name' => 'Kaos Keranjang',
            'type' => 'Atasan',
            'size' => 'M',
            'color' => 'Hitam',
            'price' => 75000,
            'stock' => $stok,
        ]);
    }

    public function test_katalog_membuka_modal_konfirmasi_bukan_submit_langsung(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();

        $html = $this->actingAs($pelanggan)->get(route('toko.index'))->assertOk()->getContent();

        // Tombol "+ Keranjang" memakai data-cart-open (dibuka lewat modal),
        // bukan form submit langsung ke route tambah keranjang.
        $this->assertStringContainsString('data-cart-open="' . $produk->id . '"', $html);
        $this->assertStringContainsString('id="cartModal"', $html);
        $this->assertStringContainsString('Masukkan keranjang', $html);
        $this->assertStringNotContainsString('action="' . route('toko.cart.add', $produk->id) . '"', $html);

        // Keranjang tetap kosong selama modal belum dikonfirmasi.
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_tambah_keranjang_hanya_lewat_post_dan_tersimpan(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk(10);

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.add', $produk->id), ['quantity' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('carts', [
            'user_id' => $pelanggan->id,
            'product_id' => $produk->id,
            'quantity' => 2,
        ]);
    }

    public function test_get_ke_route_tambah_keranjang_ditolak(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();

        $this->actingAs($pelanggan)
            ->get(route('toko.cart.add', $produk->id))
            ->assertStatus(405);

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_tamu_yang_menambah_keranjang_diarahkan_ke_login(): void
    {
        $produk = $this->produk();

        $this->post(route('toko.cart.add', $produk->id), ['quantity' => 1])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('carts', 0);
    }
}
