<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman pelanggan (katalog, detail, favorit, pesanan, login, register)
 * memakai design system La Mamora yang sama dengan landing page.
 */
class CustomerPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private function pelanggan(): User
    {
        return User::factory()->create(['role' => 'pelanggan']);
    }

    private function produk(): Product
    {
        $categoryId = \DB::table('categories')->insertGetId([
            'name' => 'Kaos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Product::create([
            'category_id' => $categoryId,
            'name' => 'Kaos Polos Navy',
            'type' => 'Kaos',
            'size' => 'M',
            'color' => 'Navy',
            'price' => 85000,
            'stock' => 12,
        ]);
    }

    public function test_katalog_memakai_stylesheet_design_system_dan_tanpa_css_lama(): void
    {
        $pelanggan = $this->pelanggan();
        $this->produk();

        $html = $this->actingAs($pelanggan)->get(route('toko.index'))->assertOk()->getContent();

        $this->assertStringContainsString('css/la-mamora.css', $html);
        $this->assertStringContainsString('css/la-mamora-shop.css', $html);

        // Palet lama (biru Shopee) tidak lagi dipakai di halaman pelanggan.
        $this->assertStringNotContainsString('#2563eb', $html);
        $this->assertStringNotContainsString('--accent:#2563eb', $html);

        // Struktur katalog tetap utuh.
        $this->assertStringContainsString('id="grid"', $html);
        $this->assertStringContainsString('id="accKategori"', $html);
        $this->assertStringContainsString('id="accHarga"', $html);
        $this->assertStringContainsString('id="rMin"', $html);
        $this->assertStringContainsString('id="rMax"', $html);
        $this->assertStringContainsString('id="search"', $html);
        $this->assertStringContainsString('id="apply"', $html);
        $this->assertStringContainsString('id="reset"', $html);
        $this->assertStringContainsString('id="buyModal"', $html);
        $this->assertStringContainsString('Kaos Polos Navy', $html);
        $this->assertStringContainsString('Beli Sekarang', $html);
    }

    public function test_halaman_pelanggan_memiliki_drawer_navigasi_dan_tombol_menu(): void
    {
        $pelanggan = $this->pelanggan();

        foreach ([route('toko.index'), route('toko.pesanan'), route('toko.favorit')] as $url) {
            $html = $this->actingAs($pelanggan)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('id="sidebarToggle"', $html);
            $this->assertStringContainsString('id="shopSidebar"', $html);
            $this->assertStringContainsString('id="sidebarOverlay"', $html);
            $this->assertStringContainsString('sidebar-open', $html);
        }
    }

    public function test_detail_produk_menggunakan_design_system_bersama(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();

        $html = $this->actingAs($pelanggan)->get(route('toko.show', $produk->id))->assertOk()->getContent();

        $this->assertStringContainsString('css/la-mamora-shop.css', $html);
        $this->assertStringContainsString('class="detail"', $html);
        $this->assertStringContainsString('Kaos Polos Navy', $html);
        $this->assertStringContainsString('Rp 85.000', $html);
        $this->assertStringContainsString('Stok tersedia: 12 pcs', $html);
        $this->assertStringNotContainsString('<style>', $html);
    }

    public function test_halaman_favorit_dan_pesanan_tetap_valid(): void
    {
        $pelanggan = $this->pelanggan();

        $favorit = $this->actingAs($pelanggan)->get(route('toko.favorit'))->assertOk()->getContent();
        $this->assertStringContainsString('Belum ada produk favorit', $favorit);
        $this->assertStringContainsString('css/la-mamora-shop.css', $favorit);

        $pesanan = $this->actingAs($pelanggan)->get(route('toko.pesanan'))->assertOk()->getContent();
        $this->assertStringContainsString('Belum ada pesanan', $pesanan);
        $this->assertStringContainsString('Total Pesanan', $pesanan);
        $this->assertStringContainsString('Total Belanja', $pesanan);
        $this->assertStringContainsString('css/la-mamora-shop.css', $pesanan);
    }

    public function test_halaman_login_dan_register_using_design_system_la_mamora(): void
    {
        foreach ([route('login'), route('register')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('css/la-mamora.css', $html);
            $this->assertStringContainsString('css/la-mamora-shop.css', $html);
            $this->assertStringContainsString('class="lm-auth"', $html);
            $this->assertStringContainsString('class="lm-auth-card"', $html);
            $this->assertStringContainsString('La Mamora.', $html);
            $this->assertStringNotContainsString('bootstrap@5.3.0', $html);
        }

        $login = $this->get(route('login'))->assertOk()->getContent();
        $this->assertStringContainsString('Masuk ke Akun', $login);
        $this->assertStringContainsString('name="email"', $login);
        $this->assertStringContainsString('name="password"', $login);

        $register = $this->get(route('register'))->assertOk()->getContent();
        $this->assertStringContainsString('Daftar Akun Baru', $register);
        $this->assertStringContainsString('name="name"', $register);
        $this->assertStringContainsString('name="password_confirmation"', $register);
    }

    public function test_halaman_auth_menampilkan_pesan_error_dan_nilai_lama(): void
    {
        // Login gagal dari halaman login kembali ke halaman login dengan pesan error.
        $this->from(route('login'))
            ->post(route('login'), ['email' => 'salah@example.test', 'password' => 'password123'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $html = $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login'), ['email' => 'salah@example.test', 'password' => 'password123'])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('lm-auth-alert', $html);
        $this->assertStringContainsString('Email atau password salah.', $html);
    }

    public function test_tamu_tidak_bisa_membuka_halaman_katalog(): void
    {
        $this->get(route('toko.index'))->assertRedirect(route('login'));
    }
}
