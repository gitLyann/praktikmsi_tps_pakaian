<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use App\Services\TransactionCustomerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pembayaran simulasi: server hanya menerima kode metode yang aktif, menyimpan
 * kode singkat, dan membuat kode referensi (VA/gerai) atau 4 digit terakhir
 * nomor HP tanpa menyimpan data sensitif.
 */
class CheckoutPaymentTest extends TestCase
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
            'name' => 'Kaos Bayar',
            'type' => 'Atasan',
            'size' => 'M',
            'color' => 'Hitam',
            'price' => 100000,
            'stock' => $stok,
        ]);
    }

    private function metode(string $group, string $code, string $name, bool $aktif = true): PaymentMethod
    {
        return PaymentMethod::create([
            'group_code' => $group,
            'code' => $code,
            'name' => $name,
            'is_active' => $aktif,
            'sort_order' => 1,
        ]);
    }

    private function isiKeranjang(User $user, Product $produk, int $qty = 1): void
    {
        $this->actingAs($user)->post(route('toko.cart.add', $produk->id), ['quantity' => $qty]);
    }

    public function test_metode_pembayaran_tidak_valid_ditolak(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();
        $this->metode('tunai', 'cod', 'Tunai');

        $this->isiKeranjang($pelanggan, $produk);

        $this->actingAs($pelanggan)
            ->from(route('toko.cart'))
            ->post(route('toko.cart.checkout'), ['payment_method' => 'tidak_ada'])
            ->assertRedirect(route('toko.cart'))
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_payments', 0);
        // Keranjang tidak dihapus saat pembayaran gagal.
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_metode_nonaktif_ditolak(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();
        $this->metode('emoney', 'emoney_gopay', 'GoPay', false);

        $this->isiKeranjang($pelanggan, $produk);

        $this->actingAs($pelanggan)
            ->from(route('toko.cart'))
            ->post(route('toko.cart.checkout'), ['payment_method' => 'emoney_gopay', 'phone' => '081234567890'])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('transaction_payments', 0);
    }

    public function test_metode_tunai_tersimpan_tanpa_kode_referensi(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();
        $this->metode('tunai', 'cod', 'Bayar di Tempat (Tunai)');

        $this->isiKeranjang($pelanggan, $produk, 2);

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.checkout'), ['payment_method' => 'cod'])
            ->assertRedirect(route('toko.pesanan'));

        $trx = Transaction::firstOrFail();
        $this->assertSame('cod', $trx->payment_method);

        $payment = TransactionPayment::where('transaction_id', $trx->id)->firstOrFail();
        $this->assertSame('cod', $payment->method_code);
        $this->assertNull($payment->reference_code);
        $this->assertNull($payment->phone_last4);
        $this->assertSame('pending', $payment->status);
    }

    public function test_metode_atm_membuat_virtual_account_dengan_prefix_bank(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();
        $this->metode('atm', 'atm_bca', 'BCA Virtual Account');

        $this->isiKeranjang($pelanggan, $produk);

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.checkout'), ['payment_method' => 'atm_bca'])
            ->assertRedirect(route('toko.pesanan'));

        $payment = TransactionPayment::firstOrFail();
        $this->assertStringStartsWith('8808', $payment->reference_code);
        $this->assertNotNull($payment->expires_at);
        $this->assertTrue($payment->expires_at->between(now()->addHours(23), now()->addHours(25)));
    }

    public function test_metode_gerai_membuat_kode_pembayaran(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();
        $this->metode('gerai', 'gerai_alfamart', 'Alfamart');

        $this->isiKeranjang($pelanggan, $produk);

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.checkout'), ['payment_method' => 'gerai_alfamart'])
            ->assertRedirect(route('toko.pesanan'));

        $payment = TransactionPayment::firstOrFail();
        $this->assertStringStartsWith('GERAI-', $payment->reference_code);
        $this->assertNotNull($payment->expires_at);
    }

    public function test_metode_emoney_menyimpan_hanya_empat_digit_terakhir(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();
        $this->metode('emoney', 'emoney_gopay', 'GoPay');

        $this->isiKeranjang($pelanggan, $produk);

        $this->actingAs($pelanggan)
            ->post(route('toko.cart.checkout'), ['payment_method' => 'emoney_gopay', 'phone' => '0812 3456 7890'])
            ->assertRedirect(route('toko.pesanan'));

        $payment = TransactionPayment::firstOrFail();
        $this->assertSame('7890', $payment->phone_last4);
        $this->assertNull($payment->reference_code);

        // Nomor HP lengkap tidak tersimpan di kolom mana pun.
        $this->assertDatabaseMissing('transaction_payments', ['phone_last4' => '081234567890']);
    }

    public function test_metode_emoney_menolak_nomor_hp_tidak_valid(): void
    {
        $pelanggan = $this->pelanggan();
        $produk = $this->produk();
        $this->metode('emoney', 'emoney_gopay', 'GoPay');

        $this->isiKeranjang($pelanggan, $produk);

        $this->actingAs($pelanggan)
            ->from(route('toko.cart'))
            ->post(route('toko.cart.checkout'), ['payment_method' => 'emoney_gopay', 'phone' => '12345'])
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('transaction_payments', 0);
    }

    public function test_riwayat_lama_dengan_metode_legacy_tetap_tampil(): void
    {
        $pelanggan = $this->pelanggan();

        Transaction::create([
            'customer_id' => app(TransactionCustomerResolver::class)->customerIdFor($pelanggan),
            'employee_id' => null,
            'date' => now(),
            'total' => 120000,
            'payment_method' => 'E-Wallet',
            'status' => 'completed',
        ]);

        $html = $this->actingAs($pelanggan)->get(route('toko.pesanan'))->assertOk()->getContent();

        $this->assertStringContainsString('E-Wallet', $html);
    }
}
