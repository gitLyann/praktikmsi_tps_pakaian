<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
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
