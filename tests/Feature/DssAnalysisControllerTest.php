<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssAnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bisa_melihat_ringkasan_analisis_dss(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $harga = DssCriterion::factory()->cost()->create(['name' => 'Harga', 'sort_order' => 1]);
        $kualitas = DssCriterion::factory()->benefit()->create(['name' => 'Kualitas', 'sort_order' => 2]);
        $supplier = Supplier::factory()->create(['name' => 'Pemasok 1']);

        // Perlu skor agar VIKOR bisa menghitung peringkat.
        DssSupplierScore::create([
            'dss_run_id' => DssRun::create([
                'name' => 'Test',
                'type' => 'vikor',
                'status' => 'draft',
            ])->id,
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $harga->id,
            'score' => 100,
        ]);

        DssSupplierScore::create([
            'dss_run_id' => DssRun::first()->id,
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $kualitas->id,
            'score' => 5,
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/dss');

        $response->assertOk();
        $response->assertJsonStructure([
            'ahp',
            'vikor',
            'restock',
        ]);
        $response->assertJsonStructure(['ahp' => ['weights'], 'vikor' => ['scores', 'ranking'], 'restock' => ['items']]);
    }

    public function test_admin_bisa_menjalankan_dan_menyimpan_analisis_dss(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $harga = DssCriterion::factory()->cost()->create(['sort_order' => 1]);
        $kualitas = DssCriterion::factory()->benefit()->create(['sort_order' => 2]);
        $supplier = Supplier::factory()->create();

        $run = DssRun::create([
            'name' => 'Skor',
            'type' => 'vikor',
            'status' => 'draft',
        ]);

        DssSupplierScore::create([
            'dss_run_id' => $run->id,
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $harga->id,
            'score' => 150,
        ]);

        DssSupplierScore::create([
            'dss_run_id' => $run->id,
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $kualitas->id,
            'score' => 4,
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/dss/analisis');

        $response->assertStatus(201);
        $response->assertJsonStructure(['run', 'analysis']);
        $this->assertDatabaseHas('dss_runs', [
            'type' => 'vikor',
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);
    }
}
