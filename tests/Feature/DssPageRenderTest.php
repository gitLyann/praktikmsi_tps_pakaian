<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssPageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_penilaian_pemasok_menampilkan_kriteria_dan_form_skor(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $supplier = Supplier::factory()->create(['name' => 'Konveksi Sentosa']);
        $criterion = DssCriterion::factory()->cost()->create(['name' => 'Harga', 'code' => 'C1']);

        $response = $this->actingAs($staff)->get(route('staff.dss'));

        $response->assertOk();
        $response->assertSee('Konveksi Sentosa');
        $response->assertSee('Harga');
        $response->assertSee(route('staff.dss.scores.store'), false);
    }

    public function test_staff_bisa_menyimpan_skor_dari_form_html(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $supplier = Supplier::factory()->create();
        $criterion = DssCriterion::factory()->benefit()->create();

        $response = $this->actingAs($staff)->post(route('staff.dss.scores.store'), [
            'scores' => [
                [
                    'supplier_id' => $supplier->id,
                    'criterion_id' => $criterion->id,
                    'score' => 82.5,
                ],
            ],
        ]);

        $response->assertRedirect(route('staff.dss'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dss_supplier_scores', [
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $criterion->id,
            'score' => 82.5,
        ]);
    }

    public function test_skor_kosong_pada_form_tidak_disimpan(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $supplier = Supplier::factory()->create();
        $criterion = DssCriterion::factory()->benefit()->create();

        $this->actingAs($staff)->post(route('staff.dss.scores.store'), [
            'scores' => [
                [
                    'supplier_id' => $supplier->id,
                    'criterion_id' => $criterion->id,
                    'score' => '',
                ],
            ],
        ])->assertRedirect(route('staff.dss'));

        $this->assertDatabaseCount('dss_supplier_scores', 0);
    }

    public function test_halaman_analisis_admin_menampilkan_ranking_vikor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $harga = DssCriterion::factory()->cost()->create(['sort_order' => 1]);
        $kualitas = DssCriterion::factory()->benefit()->create(['sort_order' => 2]);
        $supplier = Supplier::factory()->create(['name' => 'Konveksi Bandung']);
        $other = Supplier::factory()->create(['name' => 'Konveksi Jakarta']);

        $run = DssRun::create([
            'name' => 'Penilaian',
            'type' => 'vikor',
            'status' => 'draft',
        ]);

        foreach ([$supplier, $other] as $index => $item) {
            DssSupplierScore::create([
                'dss_run_id' => $run->id,
                'supplier_id' => $item->id,
                'dss_criterion_id' => $harga->id,
                'score' => 100000 + $index,
            ]);

            DssSupplierScore::create([
                'dss_run_id' => $run->id,
                'supplier_id' => $item->id,
                'dss_criterion_id' => $kualitas->id,
                'score' => 5 - $index,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.dss'));

        $response->assertOk();
        $response->assertSee('Konveksi Bandung');
        $response->assertSee('Konveksi Jakarta');
        $response->assertSee('Jalankan');
    }

    public function test_admin_bisa_menjalankan_analisis_dari_form_html_dan_run_tersimpan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $criterion = DssCriterion::factory()->benefit()->create();
        $supplier = Supplier::factory()->create();

        $run = DssRun::create([
            'name' => 'Penilaian',
            'type' => 'vikor',
            'status' => 'draft',
        ]);

        DssSupplierScore::create([
            'dss_run_id' => $run->id,
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $criterion->id,
            'score' => 90,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.dss.run'));

        $response->assertRedirect(route('admin.dss'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dss_runs', [
            'type' => 'vikor',
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);
    }

    public function test_halaman_admin_menampilkan_kartu_ringkasan_dan_tab_modul(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        DssCriterion::factory()->cost()->create(['sort_order' => 1]);
        DssCriterion::factory()->benefit()->create(['sort_order' => 2]);
        Supplier::factory()->create(['name' => 'Konveksi Bandung']);

        $response = $this->actingAs($admin)->get(route('admin.dss'));

        $response->assertOk();
        $response->assertSee('Produk Perlu Restock');
        $response->assertSee('Supplier Aktif');
        $response->assertSee('Analisis Terakhir');
        $response->assertSee('data-dss-section="dss-ringkasan"', false);
        $response->assertSee('data-dss-section="dss-bobot-ahp"', false);
        $response->assertSee('data-dss-section="dss-ranking"', false);
        $response->assertSee('data-dss-section="dss-restock"', false);
        $response->assertSee('data-dss-section="dss-pemasok"', false);
        $response->assertSee('data-dss-section="dss-riwayat"', false);
        $response->assertSee(route('admin.dss.weights.store'), false);
        $response->assertSee(route('admin.dss.suppliers.store'), false);
    }

    public function test_admin_bisa_menyimpan_bobot_ahp_dari_form_matriks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $harga = DssCriterion::factory()->cost()->create(['sort_order' => 1]);
        $kualitas = DssCriterion::factory()->benefit()->create(['sort_order' => 2]);

        $response = $this->actingAs($admin)->post(route('admin.dss.weights.store'), [
            'values' => [
                $harga->id.'-'.$kualitas->id => 3,
            ],
        ]);

        $response->assertRedirect(route('admin.dss').'#dss-bobot-ahp');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dss_criteria_comparisons', [
            'criterion_a_id' => $harga->id,
            'criterion_b_id' => $kualitas->id,
            'value' => 3,
        ]);

        $this->assertDatabaseHas('dss_criteria_comparisons', [
            'criterion_a_id' => $kualitas->id,
            'criterion_b_id' => $harga->id,
            'value' => 0.3333,
        ]);
    }

    public function test_admin_bisa_membuat_pengajuan_restock_dari_rekomendasi_dss(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$product, $supplier] = $this->skenarioProdukPerluRestock();

        $response = $this->actingAs($admin)->post(route('admin.dss.restock.store'), [
            'product_id' => $product->id,
        ]);

        $response->assertRedirect(route('admin.dss').'#dss-restock');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('restock_requests', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('dss_restock_recommendations', [
            'product_id' => $product->id,
        ]);
    }

    public function test_staff_melihat_rekomendasi_restock_dan_bisa_mengajukan(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        [$product] = $this->skenarioProdukPerluRestock();

        $response = $this->actingAs($staff)->get(route('staff.dss'));

        $response->assertOk();
        $response->assertSee('Ajukan Restock');
        $response->assertSee(route('staff.dss.restock.store'), false);
        $response->assertSee('Status Pengajuan Restock');

        $response = $this->actingAs($staff)->post(route('staff.dss.restock.store'), [
            'product_id' => $product->id,
        ]);

        $response->assertRedirect(route('staff.dss').'#dss-restock');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('restock_requests', [
            'product_id' => $product->id,
            'status' => 'pending',
        ]);
    }

    public function test_halaman_staff_menampilkan_ranking_supplier_read_only(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $criterion = DssCriterion::factory()->benefit()->create();
        $supplier = Supplier::factory()->create(['name' => 'Konveksi Ranking']);
        $other = Supplier::factory()->create(['name' => 'Konveksi Kedua']);

        $run = DssRun::create([
            'name' => 'Penilaian',
            'type' => 'vikor',
            'status' => 'draft',
        ]);

        DssSupplierScore::create([
            'dss_run_id' => $run->id,
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $criterion->id,
            'score' => 90,
        ]);

        DssSupplierScore::create([
            'dss_run_id' => $run->id,
            'supplier_id' => $other->id,
            'dss_criterion_id' => $criterion->id,
            'score' => 60,
        ]);

        $response = $this->actingAs($staff)->get(route('staff.dss'));

        $response->assertOk();
        $response->assertSee('Ranking Supplier (Read Only)');
        $response->assertSee('Konveksi Ranking');
        $response->assertSee('Konveksi Kedua');
    }

    /**
     * Skenario satu produk yang perlu restock: stok kosong, ada penjualan
     * 30 hari terakhir, dan pemasok dengan penawaran harga belinya.
     *
     * @return array{0: Product, 1: Supplier}
     */
    private function skenarioProdukPerluRestock(): array
    {
        $supplier = Supplier::factory()->create([
            'lead_time_days' => 7,
            'min_order_qty' => 10,
        ]);

        $product = Product::factory()->create(['stock' => 0]);

        ProductSupplier::factory()->create([
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'price' => 85000,
            'min_order_qty' => 10,
            'lead_time_days' => 7,
        ]);

        $transaction = Transaction::create([
            'date' => now()->subDays(3),
            'total' => 170000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 170000,
        ]);

        return [$product, $supplier];
    }

    public function test_sidebar_memuat_nav_dss(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('staff.dss'))
            ->assertOk()
            ->assertSee(route('staff.dss'), false);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dss'))
            ->assertOk()
            ->assertSee(route('admin.dss'), false);
    }
}
