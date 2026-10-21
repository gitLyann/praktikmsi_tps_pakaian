<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\RestockRequest;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Form staff (pengajuan restock & tambah master produk) menyimpan penawaran
 * pemasok ke tabel product_suppliers.
 */
class StaffSupplierOfferTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $staff = User::factory()->create(['role' => 'staff']);

        \DB::table('employees')->insert([
            'user_id' => $staff->id,
            'name' => $staff->name,
            'position' => 'Kasir',
        ]);

        return $staff;
    }

    private function product(): Product
    {
        $categoryId = \DB::table('categories')->insertGetId([
            'name' => 'Kaos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Product::create([
            'category_id' => $categoryId,
            'name' => 'Kaos Polos',
            'type' => 'Kaos',
            'price' => 50000,
            'stock' => 2,
        ]);
    }

    public function test_pengajuan_restock_menyimpan_penawaran_pemasok(): void
    {
        $staff = $this->staff();
        $product = $this->product();
        $supplier = Supplier::factory()->create(['name' => 'Konveksi Aktif', 'is_active' => true]);

        $response = $this->actingAs($staff)->post(route('staff.restock.store'), [
            'product_id' => $product->id,
            'jumlah_restock' => 30,
            'supplier_id' => $supplier->id,
            'supplier_price' => 35000,
            'supplier_lead_time_days' => 12,
            'supplier_min_order_qty' => 25,
        ]);

        $response->assertRedirect(route('staff.dashboard').'#pengajuan-restock');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('restock_requests', [
            'product_id' => $product->id,
            'jumlah_restock' => 30,
            'status' => 'pending',
            'supplier_id' => $supplier->id,
        ]);

        $offer = ProductSupplier::where('product_id', $product->id)
            ->where('supplier_id', $supplier->id)
            ->firstOrFail();

        $this->assertSame(35000.0, (float) $offer->price);
        $this->assertSame(12, $offer->lead_time_days);
        $this->assertSame(25, $offer->min_order_qty);
        $this->assertFalse((bool) $offer->is_preferred);
    }

    public function test_penawaran_pemasok_diperbarui_bukan_ditambahkan_ganda(): void
    {
        $staff = $this->staff();
        $product = $this->product();
        $supplier = Supplier::factory()->create(['is_active' => true]);

        $payload = [
            'product_id' => $product->id,
            'jumlah_restock' => 10,
            'supplier_id' => $supplier->id,
            'supplier_price' => 30000,
            'supplier_lead_time_days' => 7,
            'supplier_min_order_qty' => 10,
        ];

        $this->actingAs($staff)->post(route('staff.restock.store'), $payload)->assertRedirect();

        $payload['supplier_price'] = 28000;
        $payload['supplier_lead_time_days'] = 5;
        $this->actingAs($staff)->post(route('staff.restock.store'), $payload)->assertRedirect();

        $this->assertSame(1, ProductSupplier::where('product_id', $product->id)->count());

        $offer = ProductSupplier::where('product_id', $product->id)->firstOrFail();
        $this->assertSame(28000.0, (float) $offer->price);
        $this->assertSame(5, $offer->lead_time_days);
    }

    public function test_tambah_master_produk_menyimpan_penawaran_pemasok(): void
    {
        $staff = $this->staff();
        $supplier = Supplier::factory()->create(['is_active' => true]);

        $this->actingAs($staff)->post(route('staff.products.store'), [
            'category' => 'Outerwear',
            'name' => 'Jaket Bomber',
            'type' => 'Atasan',
            'price' => 250000,
            'supplier_id' => $supplier->id,
            'supplier_price' => 180000,
            'supplier_lead_time_days' => 14,
            'supplier_min_order_qty' => 30,
        ])->assertRedirect();

        $product = Product::where('name', 'Jaket Bomber')->firstOrFail();

        $offer = ProductSupplier::where('product_id', $product->id)
            ->where('supplier_id', $supplier->id)
            ->firstOrFail();

        $this->assertSame(180000.0, (float) $offer->price);
        $this->assertSame(14, $offer->lead_time_days);
        $this->assertSame(30, $offer->min_order_qty);
    }

    public function test_tanpa_supplier_tidak_ada_relasi_penawaran_yang_dibuat(): void
    {
        $staff = $this->staff();
        $product = $this->product();

        $this->actingAs($staff)->post(route('staff.restock.store'), [
            'product_id' => $product->id,
            'jumlah_restock' => 8,
        ])->assertRedirect();

        $this->assertSame(0, ProductSupplier::count());

        $request = RestockRequest::latest('id')->firstOrFail();
        $this->assertNull($request->supplier_id);
    }

    public function test_dropdown_pemasok_hanya_menampilkan_pemasok_aktif(): void
    {
        $staff = $this->staff();
        $aktif = Supplier::factory()->create(['name' => 'Konveksi Aktif', 'is_active' => true]);
        Supplier::factory()->create(['name' => 'Konveksi Nonaktif', 'is_active' => false]);

        $html = $this->actingAs($staff)->get(route('staff.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('name="supplier_id"', $html);
        $this->assertStringContainsString('Konveksi Aktif', $html);
        $this->assertStringNotContainsString('Konveksi Nonaktif', $html);

        // Link ke Master Supplier tersedia di kedua form.
        $this->assertStringContainsString(route('supplier.master'), $html);
        $this->assertStringContainsString('Tambah di Master Supplier', $html);
        $this->assertStringContainsString('Harga Beli (Rp)', $html);
        $this->assertStringContainsString('Lead Time (hari)', $html);
        $this->assertStringContainsString('Minimal Order (pcs)', $html);
        $this->assertStringContainsString('value="'.$aktif->id.'"', $html);
    }

    public function test_supplier_tidak_aktif_menolak_pengajuan_restock(): void
    {
        $staff = $this->staff();
        $product = $this->product();
        $supplier = Supplier::factory()->create(['is_active' => false]);

        $this->actingAs($staff)->post(route('staff.restock.store'), [
            'product_id' => $product->id,
            'jumlah_restock' => 5,
            'supplier_id' => $supplier->id,
        ])->assertSessionHasErrors('supplier_id');

        $this->assertSame(0, ProductSupplier::count());
    }
}
