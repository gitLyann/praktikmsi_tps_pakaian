<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionCustomerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Isolasi data favorit dan riwayat transaksi.
 *
 * Kedua data ini harusnya hanya bisa dibaca dan diubah oleh pemiliknya.
 */
class FavoriteIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function pelanggan(string $nama = 'Pelanggan'): User
    {
        return User::factory()->create(['role' => 'pelanggan', 'name' => $nama]);
    }

    private function produk(string $nama = 'Kaos Polos'): Product
    {
        $categoryId = Category::query()->create(['name' => 'Kaos'])->id;

        return Product::create([
            'category_id' => $categoryId,
            'name' => $nama,
            'type' => 'Kaos',
            'size' => 'M',
            'color' => 'Navy',
            'price' => 85000,
            'stock' => 10,
        ]);
    }

    public function test_favorit_pelanggan_a_tidak_muncul_di_daftar_favorit_pelanggan_b(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $produk = $this->produk('Kaos Favorit A');

        $this->actingAs($pelangganA)
            ->post(route('toko.favorit.toggle', $produk->id))
            ->assertRedirect();

        $html = $this->actingAs($pelangganB)->get(route('toko.favorit'))->assertOk()->getContent();

        $this->assertStringContainsString('Belum ada produk favorit', $html);
        $this->assertStringNotContainsString('Kaos Favorit A', $html);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $pelangganA->id,
            'product_id' => $produk->id,
        ]);
    }

    public function test_toggle_favorit_pelanggan_b_tidak_menghapus_favorit_pelanggan_a(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $produk = $this->produk();

        $this->actingAs($pelangganA)->post(route('toko.favorit.toggle', $produk->id));

        $this->actingAs($pelangganB)->post(route('toko.favorit.toggle', $produk->id));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $pelangganA->id,
            'product_id' => $produk->id,
        ]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $pelangganB->id,
            'product_id' => $produk->id,
        ]);
        $this->assertDatabaseCount('favorites', 2);
    }

    public function test_riwayat_transaksi_pelanggan_a_tidak_muncul_di_pelanggan_b(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $resolver = app(TransactionCustomerResolver::class);

        $transaksiA = Transaction::create([
            'customer_id' => $resolver->customerIdFor($pelangganA),
            'employee_id' => null,
            'date' => now(),
            'total' => 150000,
            'payment_method' => 'online',
            'status' => 'completed',
        ]);

        $transaksiB = Transaction::create([
            'customer_id' => $resolver->customerIdFor($pelangganB),
            'employee_id' => null,
            'date' => now(),
            'total' => 90000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $html = $this->actingAs($pelangganA)->get(route('toko.pesanan'))->assertOk()->getContent();

        $this->assertStringContainsString((string) str_pad($transaksiA->id, 5, '0', STR_PAD_LEFT), $html);
        $this->assertStringNotContainsString((string) str_pad($transaksiB->id, 5, '0', STR_PAD_LEFT), $html);
        $this->assertStringContainsString('Rp 150.000', $html);
        $this->assertStringNotContainsString('Rp 90.000', $html);
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_favorit_dan_pesanan(): void
    {
        $this->get(route('toko.favorit'))->assertRedirect(route('login'));
        $this->get(route('toko.pesanan'))->assertRedirect(route('login'));
    }

    public function test_profil_hanya_menampilkan_data_user_yang_login(): void
    {
        $pelangganA = $this->pelanggan('Budi Santoso');
        $pelangganB = $this->pelanggan('Siti Aminah');

        $html = $this->actingAs($pelangganA)->get(route('toko.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringNotContainsString('Siti Aminah', $html);
    }
}
