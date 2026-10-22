<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Isolasi data keranjang pelanggan.
 *
 * Keranjang disimpan di tabel `carts` dan selalu di-scope ke user yang login.
 * Tidak ada endpoint yang menerima user_id dari request.
 */
class CartIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function pelanggan(string $nama = 'Pelanggan'): User
    {
        return User::factory()->create(['role' => 'pelanggan', 'name' => $nama]);
    }

    private function produk(string $nama = 'Kaos Polos', int $stok = 10): Product
    {
        $categoryId = Category::query()->create(['name' => 'Kaos'])->id;

        return Product::create([
            'category_id' => $categoryId,
            'name' => $nama,
            'type' => 'Kaos',
            'size' => 'M',
            'color' => 'Navy',
            'price' => 85000,
            'stock' => $stok,
        ]);
    }

    public function test_keranjang_pelanggan_a_terlihat_pada_keranjang_a(): void
    {
        $pelangganA = $this->pelanggan('A');
        $produk = $this->produk();

        $this->actingAs($pelangganA)
            ->post(route('toko.cart.add', $produk->id), ['quantity' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('carts', [
            'user_id' => $pelangganA->id,
            'product_id' => $produk->id,
            'quantity' => 2,
        ]);
    }

    public function test_keranjang_pelanggan_b_kosong_saat_pelanggan_a_memiliki_item(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $produk = $this->produk();

        $this->actingAs($pelangganA)
            ->post(route('toko.cart.add', $produk->id), ['quantity' => 3]);

        $html = $this->actingAs($pelangganB)->get(route('toko.cart'))->assertOk()->getContent();

        $this->assertStringContainsString('Keranjang masih kosong', $html);
        $this->assertStringNotContainsString($produk->name, $html);
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_pelanggan_b_tidak_bisa_mengubah_item_keranjang_milik_pelanggan_a(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $produk = $this->produk();

        $this->actingAs($pelangganA)
            ->post(route('toko.cart.add', $produk->id), ['quantity' => 2]);

        $cartA = Cart::where('user_id', $pelangganA->id)->firstOrFail();

        $this->actingAs($pelangganB)
            ->post(route('toko.cart.update', $cartA->id), ['quantity' => 9])
            ->assertNotFound();

        $this->assertDatabaseHas('carts', [
            'id' => $cartA->id,
            'user_id' => $pelangganA->id,
            'quantity' => 2,
        ]);
    }

    public function test_pelanggan_b_tidak_bisa_menghapus_item_keranjang_milik_pelanggan_a(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $produk = $this->produk();

        $this->actingAs($pelangganA)
            ->post(route('toko.cart.add', $produk->id), ['quantity' => 2]);

        $cartA = Cart::where('user_id', $pelangganA->id)->firstOrFail();

        $this->actingAs($pelangganB)
            ->post(route('toko.cart.remove', $cartA->id))
            ->assertNotFound();

        $this->assertDatabaseHas('carts', [
            'id' => $cartA->id,
            'user_id' => $pelangganA->id,
        ]);
    }

    public function test_kosongkan_keranjang_hanya_menghapus_item_pengguna_sendiri(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $produkA = $this->produk('Kaos A');
        $produkB = $this->produk('Kaos B');

        $this->actingAs($pelangganA)->post(route('toko.cart.add', $produkA->id), ['quantity' => 1]);
        $this->actingAs($pelangganB)->post(route('toko.cart.add', $produkB->id), ['quantity' => 1]);

        $this->actingAs($pelangganA)->post(route('toko.cart.clear'))->assertRedirect();

        $this->assertDatabaseMissing('carts', ['user_id' => $pelangganA->id]);
        $this->assertDatabaseHas('carts', [
            'user_id' => $pelangganB->id,
            'product_id' => $produkB->id,
        ]);
    }

    public function test_duapelanggan_memakai_produk_sama_dengan_jumlah_berbeda(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $produk = $this->produk();

        $this->actingAs($pelangganA)->post(route('toko.cart.add', $produk->id), ['quantity' => 1]);
        $this->actingAs($pelangganB)->post(route('toko.cart.add', $produk->id), ['quantity' => 5]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $pelangganA->id,
            'product_id' => $produk->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('carts', [
            'user_id' => $pelangganB->id,
            'product_id' => $produk->id,
            'quantity' => 5,
        ]);
    }

    public function test_menambah_produk_sama_menggabungkan_jumlah(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();

        $this->actingAs($pelanggan)->post(route('toko.cart.add', $produk->id), ['quantity' => 2]);
        $this->actingAs($pelanggan)->post(route('toko.cart.add', $produk->id), ['quantity' => 3]);

        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseHas('carts', [
            'user_id' => $pelanggan->id,
            'product_id' => $produk->id,
            'quantity' => 5,
        ]);
    }

    public function test_jumlah_keranjang_tidak_boleh_kurang_dari_satu_atau_lebih_dari_stok(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk('Kaos Stok Tipis', 2);

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.add', $produk->id), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.add', $produk->id), ['quantity' => 5])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_keranjang(): void
    {
        $this->get(route('toko.cart'))->assertRedirect(route('login'));
    }

    public function test_tamu_yang_menambah_keranjang_diarahkan_ke_login_kemudian_kembali(): void
    {
        $produk = $this->produk();
        $pelanggan = $this->pelanggan();

        $this->post(route('toko.cart.intent', $produk->id), [
            'quantity' => 2,
            'return_to' => route('toko.index'),
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('carts', 0);

        $this->post(route('login'), [
            'email' => $pelanggan->email,
            'password' => 'password',
        ])->assertRedirect(route('toko.index'));

        $this->assertDatabaseHas('carts', [
            'user_id' => $pelanggan->id,
            'product_id' => $produk->id,
            'quantity' => 2,
        ]);
    }

    public function test_staff_dan_admin_tidak_bisa_mengakses_keranjang_pelanggan(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($staff)->get(route('toko.cart'))->assertForbidden();
        $this->actingAs($admin)->get(route('toko.cart'))->assertForbidden();
    }

    public function test_halaman_keranjang_mengikuti_design_system_la_mamora(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();

        $this->actingAs($pelanggan)->post(route('toko.cart.add', $produk->id), ['quantity' => 1]);

        $html = $this->actingAs($pelanggan)->get(route('toko.cart'))->assertOk()->getContent();

        $this->assertStringContainsString('css/la-mamora-shop.css', $html);
        $this->assertStringContainsString('Keranjang Belanja', $html);
        $this->assertStringContainsString('Checkout Sekarang', $html);
        $this->assertStringContainsString('Kosongkan Keranjang', $html);
        $this->assertStringContainsString($produk->name, $html);
    }

    public function test_navbar_menampilkan_badge_jumlah_keranjang(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();

        $this->actingAs($pelanggan)->post(route('toko.cart.add', $produk->id), ['quantity' => 3]);

        $html = $this->actingAs($pelanggan)->get(route('toko.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('toko.cart'), $html);
        $this->assertStringContainsString('>3<', $html);
    }

    public function test_halaman_katalog_tidak_menampilkan_tombol_keranjang_untuk_staff_dan_admin(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $produk = $this->produk();

        foreach ([$staff, $admin] as $user) {
            $html = $this->actingAs($user)->get(route('toko.index'))->assertOk()->getContent();

            $this->assertStringNotContainsString(route('toko.cart.add', $produk->id), $html);
            $this->assertStringNotContainsString(route('toko.cart.intent', $produk->id), $html);
            $this->assertStringNotContainsString(route('toko.cart'), $html);
        }
    }

    public function test_staff_dan_admin_tidak_bisa_membuat_item_keranjang(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $produk = $this->produk();

        foreach ([$staff, $admin] as $user) {
            $this->actingAs($user)
                ->post(route('toko.cart.intent', $produk->id), ['quantity' => 1])
                ->assertRedirect(route('toko.index'));
        }

        $this->assertDatabaseCount('carts', 0);
    }

    public function test_niat_keranjang_tamu_dengan_tujuan_luar_ditolak(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();

        $this->post(route('toko.cart.intent', $produk->id), [
            'quantity' => 1,
            'return_to' => 'https://evil.example.com/pencurian',
        ])->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => $pelanggan->email,
            'password' => 'password',
        ])->assertRedirect(route('toko.index'));
    }
}
