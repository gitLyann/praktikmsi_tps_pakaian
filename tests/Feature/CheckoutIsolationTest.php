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
 * Checkout hanya boleh memproses keranjang milik pengguna yang sedang login.
 *
 * Transaksi disimpan di database dalam satu transaksi DB, stok produk
 * dikunci saat proses, dan keranjang pemilik dihapus setelah berhasil.
 */
class CheckoutIsolationTest extends TestCase
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

    /**
     * Id yang bisa dipakai untuk menyimpan transaksi milik user ini.
     *
     * @return array<int, int>
     */
    private function customerIdsFor(User $user): array
    {
        return app(TransactionCustomerResolver::class)->customerIdsFor($user);
    }

    public function test_checkout_memproses_keranjang_sendiri_saja_dan_mengurangi_stok(): void
    {
        $pelangganA = $this->pelanggan('A');
        $pelangganB = $this->pelanggan('B');
        $produk = $this->produk('Kaos Checkout', 10);

        $this->actingAs($pelangganA)->post(route('toko.cart.add', $produk->id), ['quantity' => 2]);
        $this->actingAs($pelangganB)->post(route('toko.cart.add', $produk->id), ['quantity' => 3]);

        $this->actingAs($pelangganA)
            ->post(route('toko.cart.checkout'), ['payment_method' => 'online'])
            ->assertRedirect(route('toko.pesanan'));

        // Hanya transaksi milik A yang dibuat.
        $this->assertDatabaseCount('transactions', 1);
        $transaksi = Transaction::whereIn('customer_id', $this->customerIdsFor($pelangganA))->firstOrFail();
        $this->assertEquals(2, $transaksi->details()->sum('quantity'));
        $this->assertEquals(170000, (float) $transaksi->total);

        // Keranjang A kosong, keranjang B utuh.
        $this->assertDatabaseMissing('carts', ['user_id' => $pelangganA->id]);
        $this->assertDatabaseHas('carts', [
            'user_id' => $pelangganB->id,
            'product_id' => $produk->id,
            'quantity' => 3,
        ]);

        // Stok berkurang sesuai jumlah yang dibeli A saja.
        $this->assertEquals(8, $produk->fresh()->stock);
    }

    public function test_detail_transaksi_mencatat_produk_dan_subtotal(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk('Kaos Detail', 10);

        $this->actingAs($pelanggan)->post(route('toko.cart.add', $produk->id), ['quantity' => 3]);
        $this->actingAs($pelanggan)
            ->post(route('toko.cart.checkout'), ['payment_method' => 'online'])
            ->assertRedirect(route('toko.pesanan'));

        $transaksi = Transaction::whereIn('customer_id', $this->customerIdsFor($pelanggan))->firstOrFail();
        $detail = TransactionDetail::where('transaction_id', $transaksi->id)->firstOrFail();

        $this->assertEquals($produk->id, $detail->product_id);
        $this->assertEquals(3, $detail->quantity);
        $this->assertEquals(255000, (float) $detail->subtotal);
    }

    public function test_checkout_dengan_keranjang_kosong_menolak_pembelian(): void
    {
        $pelanggan = $this->pelanggan();

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.checkout'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_ditolak_saat_stok_tidak_mencukupi(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk('Kaos Habis', 5);

        $this->actingAs($pelanggan)->post(route('toko.cart.add', $produk->id), ['quantity' => 5]);
        $produk->update(['stock' => 1]);

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.checkout'))
            ->assertStatus(409);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('carts', [
            'user_id' => $pelanggan->id,
            'product_id' => $produk->id,
        ]);
    }

    public function test_checkout_tidak_boleh_dipakai_oleh_staff_dan_admin(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($staff)->post(route('toko.cart.checkout'))->assertForbidden();
        $this->actingAs($admin)->post(route('toko.cart.checkout'))->assertForbidden();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_tamu_diarahkan_ke_login_saat_checkout(): void
    {
        $this->post(route('toko.cart.checkout'))->assertRedirect(route('login'));

        $this->assertDatabaseCount('transactions', 0);
    }
}
