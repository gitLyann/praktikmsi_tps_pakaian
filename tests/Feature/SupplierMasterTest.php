<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Master Supplier.
 *
 * Admin punya hak penuh (tambah, ubah, hapus), staff hanya tambah dan ubah.
 */
class SupplierMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dan_staff_bisa_membuka_halaman_master_supplier(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        foreach ([$admin, $staff] as $user) {
            $response = $this->actingAs($user)->get(route('supplier.master'));

            $response->assertOk();
            $response->assertSee('Master Supplier');
            $response->assertSee('Kategori Bahan / Produk yang Dipasok');
            $response->assertSee('Lead Time Rata-rata (hari)');
            $response->assertSee('Minimal Order (pcs)');
            $response->assertSee(route('supplier.master.store'), false);
        }
    }

    public function test_pelanggan_tidak_bisa_membuka_master_supplier(): void
    {
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);

        $this->actingAs($pelanggan)->get(route('supplier.master'))->assertForbidden();
    }

    public function test_admin_menambah_pemasok_dengan_kode_otomatis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('supplier.master.store'), [
            'name' => 'Konveksi Mandiri',
            'code' => '',
            'contact_name' => 'Budi Santoso',
            'phone' => '08123456789',
            'email' => 'mandiri@example.test',
            'address' => 'Jl. Merdeka No. 10',
            'city' => 'Bandung',
            'supplied_categories' => 'Kaos, Kemeja',
            'lead_time_days' => 9,
            'min_order_qty' => 25,
            'rating' => 4.5,
            'is_active' => 1,
            'notes' => 'Supplier cadangan',
        ]);

        $response->assertRedirect(route('supplier.master'));
        $response->assertSessionHas('success');

        $supplier = Supplier::where('name', 'Konveksi Mandiri')->firstOrFail();

        $this->assertMatchesRegularExpression('/^SUP-\d{3,}$/', $supplier->code);
        $this->assertSame('Konveksi Mandiri', $supplier->name);
        $this->assertSame(9, $supplier->lead_time_days);
        $this->assertSame(25, $supplier->min_order_qty);
        $this->assertSame(['Kaos', 'Kemeja'], $supplier->suppliedCategoryList());
        $this->assertTrue($supplier->is_active);
    }

    public function test_staff_bisa_menambah_pemasok(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->post(route('supplier.master.store'), [
            'name' => 'Konveksi Staff',
            'code' => 'SUP-STAFF',
            'lead_time_days' => 5,
            'min_order_qty' => 10,
        ])->assertRedirect(route('supplier.master'));

        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-STAFF', 'name' => 'Konveksi Staff']);
    }

    public function test_staff_dan_admin_bisa_mengubah_pemasok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $supplier = Supplier::factory()->create(['name' => 'Konveksi Lama', 'code' => 'SUP-OLD']);

        foreach ([$admin, $staff] as $user) {
            $this->actingAs($user)->put(route('supplier.master.update', $supplier->id), [
                'name' => 'Konveksi Baru',
                'code' => 'SUP-OLD',
                'lead_time_days' => 3,
                'min_order_qty' => 5,
                'supplied_categories' => 'Celana',
            ])->assertRedirect(route('supplier.master'));
        }

        $supplier->refresh();

        $this->assertSame('Konveksi Baru', $supplier->name);
        $this->assertSame(3, $supplier->lead_time_days);
        $this->assertSame(['Celana'], $supplier->suppliedCategoryList());
    }

    public function test_hanya_admin_yang_boleh_menghapus_pemasok(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($staff)
            ->delete(route('supplier.master.destroy', $supplier->id))
            ->assertForbidden();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_admin_menghapus_pemasok_yang_belum_dipakai(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->delete(route('supplier.master.destroy', $supplier->id))
            ->assertRedirect(route('supplier.master'));

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_pemasok_yang_sudah_dipakai_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();

        \App\Models\ProductSupplier::factory()->create(['supplier_id' => $supplier->id]);

        $this->actingAs($admin)
            ->delete(route('supplier.master.destroy', $supplier->id))
            ->assertRedirect(route('supplier.master'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_pencarian_dan_filter_status_menyaring_daftar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Supplier::factory()->create(['name' => 'Konveksi Bandung', 'code' => 'SUP-001', 'city' => 'Bandung']);
        Supplier::factory()->create(['name' => 'Konveksi Semarang', 'code' => 'SUP-002', 'city' => 'Semarang', 'is_active' => false]);

        // Pencarian berdasarkan nama.
        $html = $this->actingAs($admin)->get(route('supplier.master', ['q' => 'Bandung']))->getContent();
        $this->assertStringContainsString('Konveksi Bandung', $html);
        $this->assertStringNotContainsString('Konveksi Semarang', $html);

        // Filter status nonaktif.
        $html = $this->actingAs($admin)->get(route('supplier.master', ['status' => 'inactive']))->getContent();
        $this->assertStringContainsString('Konveksi Semarang', $html);
        $this->assertStringNotContainsString('Konveksi Bandung', $html);

        // Filter status aktif.
        $html = $this->actingAs($admin)->get(route('supplier.master', ['status' => 'active']))->getContent();
        $this->assertStringContainsString('Konveksi Bandung', $html);
        $this->assertStringNotContainsString('Konveksi Semarang', $html);
    }

    public function test_halaman_edit_menampilkan_data_pemasok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create([
            'name' => 'Konveksi Edit',
            'code' => 'SUP-EDIT',
            'contact_name' => 'Siti',
            'city' => 'Yogyakarta',
            'lead_time_days' => 11,
            'min_order_qty' => 30,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('supplier.master', ['edit' => $supplier->id]))
            ->getContent();

        $this->assertStringContainsString('Ubah Data Pemasok', $html);
        $this->assertStringContainsString(route('supplier.master.update', $supplier->id), $html);
        $this->assertStringContainsString('Konveksi Edit', $html);
        $this->assertStringContainsString('SUP-EDIT', $html);
    }

    public function test_kategori_bahan_bisa_diusul_dari_master_kategori_produk(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::create(['name' => 'Baju Muslim']);

        $html = $this->actingAs($admin)->get(route('supplier.master'))->getContent();

        $this->assertStringContainsString('Baju Muslim', $html);
    }
}
