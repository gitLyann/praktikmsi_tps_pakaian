<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssCriterionComparison;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssSupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bisa_menambah_pemasok_baru(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.dss.suppliers.store'), [
            'name' => 'Konveksi Solo Baru',
            'code' => 'SUP-099',
            'contact_name' => 'Bagus',
            'lead_time_days' => 6,
            'min_order_qty' => 12,
            'rating' => 4.2,
        ]);

        $response->assertRedirect(route('admin.dss').'#dss-pemasok');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Konveksi Solo Baru',
            'code' => 'SUP-099',
            'lead_time_days' => 6,
        ]);
    }

    public function test_kode_pemasok_harus_unik(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Supplier::factory()->create(['code' => 'SUP-001']);

        $this->actingAs($admin)->post(route('admin.dss.suppliers.store'), [
            'name' => 'Konveksi Duplikat',
            'code' => 'SUP-001',
            'lead_time_days' => 5,
            'min_order_qty' => 10,
        ])->assertSessionHasErrors('code');
    }

    public function test_admin_bisa_mengubah_data_pemasok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create(['name' => 'Konveksi Lama', 'code' => 'SUP-010']);

        $this->actingAs($admin)->put(route('admin.dss.suppliers.update', $supplier), [
            'name' => 'Konveksi Baru',
            'code' => 'SUP-011',
            'lead_time_days' => 9,
            'min_order_qty' => 30,
        ])->assertRedirect(route('admin.dss').'#dss-pemasok');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Konveksi Baru',
            'code' => 'SUP-011',
        ]);
    }

    public function test_admin_bisa_menonaktifkan_pemasok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->post(route('admin.dss.suppliers.toggle', $supplier))
            ->assertRedirect(route('admin.dss').'#dss-pemasok');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);
    }

    public function test_pemasok_yang_sudah_dipakai_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        ProductSupplier::factory()->create(['supplier_id' => $supplier->id]);

        $response = $this->actingAs($admin)->delete(route('admin.dss.suppliers.destroy', $supplier));

        $response->assertRedirect(route('admin.dss').'#dss-pemasok');
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_pemasok_belum_terpakai_tetap_bisa_dihapus(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)->delete(route('admin.dss.suppliers.destroy', $supplier))
            ->assertRedirect(route('admin.dss').'#dss-pemasok');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_admin_bisa_menambah_penawaran_produk_pemasok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)->post(route('admin.dss.suppliers.offers.store', $supplier), [
            'product_id' => $product->id,
            'price' => 120000,
            'min_order_qty' => 15,
            'lead_time_days' => 5,
            'is_preferred' => '1',
        ])->assertRedirect(route('admin.dss').'#dss-pemasok');

        $this->assertDatabaseHas('product_suppliers', [
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'price' => 120000,
            'min_order_qty' => 15,
            'is_preferred' => true,
        ]);
    }

    public function test_admin_bisa_menghapus_penawaran_produk_pemasok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $offer = ProductSupplier::factory()->create(['supplier_id' => $supplier->id]);

        $this->actingAs($admin)->delete(route('admin.dss.suppliers.offers.destroy', [$supplier->id, $offer->id]))
            ->assertRedirect(route('admin.dss').'#dss-pemasok');

        $this->assertDatabaseMissing('product_suppliers', ['id' => $offer->id]);
    }

    public function test_staff_tidak_bisa_mengakses_halaman_kelola_pemasok(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('admin.dss'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.dss.suppliers.store'), [
            'name' => 'Konveksi Ilegal',
            'code' => 'SUP-777',
            'lead_time_days' => 5,
            'min_order_qty' => 10,
        ])->assertForbidden();
    }
}
