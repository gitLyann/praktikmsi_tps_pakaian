<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssScoreControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_bisa_menyimpan_skor_penilaian_pemasok(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $supplier = Supplier::factory()->create();
        $criterion = DssCriterion::factory()->benefit()->create(['sort_order' => 1]);

        $response = $this->actingAs($staff)->postJson('/staff/dss/skor', [
            'scores' => [
                [
                    'supplier_id' => $supplier->id,
                    'criterion_id' => $criterion->id,
                    'score' => 85,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'saved' => 1,
        ]);

        $this->assertDatabaseHas('dss_supplier_scores', [
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $criterion->id,
            'score' => 85,
        ]);

        $this->assertDatabaseHas('dss_runs', [
            'type' => 'vikor',
            'status' => 'draft',
        ]);
    }

    public function test_staff_dapat_melihat_daftar_pemasok_dan_kriteria_penilaian(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Supplier::factory()->create(['name' => 'Pemasok Uji']);
        DssCriterion::factory()->cost()->create(['name' => 'Harga']);

        $response = $this->actingAs($staff)->getJson('/staff/dss');

        $response->assertOk();
        $response->assertJsonStructure([
            'criteria',
            'suppliers',
            'run',
        ]);

        $data = $response->json();
        $this->assertTrue(collect($data['suppliers'])->contains(fn ($s) => $s['supplier_name'] === 'Pemasok Uji'));
        $this->assertTrue(collect($data['criteria'])->contains(fn ($c) => $c['name'] === 'Harga'));
    }
}
