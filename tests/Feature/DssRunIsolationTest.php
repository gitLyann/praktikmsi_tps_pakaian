<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use App\Services\DssAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssRunIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_analisis_memakai_skor_dari_run_draft_terbaru_saja(): void
    {
        $criterion = DssCriterion::factory()->benefit()->create(['sort_order' => 1]);
        $lama = Supplier::factory()->create(['name' => 'Pemasok Lama']);
        $baru = Supplier::factory()->create(['name' => 'Pemasok Baru']);

        $runLama = DssRun::create([
            'name' => 'Penilaian Lama',
            'type' => 'vikor',
            'status' => 'draft',
            'criteria_count' => 1,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $runBaru = DssRun::create([
            'name' => 'Penilaian Baru',
            'type' => 'vikor',
            'status' => 'draft',
            'criteria_count' => 1,
        ]);

        $this->simpanSkor($runLama->id, $criterion->id, $lama->id, 5);
        $this->simpanSkor($runLama->id, $criterion->id, $baru->id, 1);
        $this->simpanSkor($runBaru->id, $criterion->id, $lama->id, 1);
        $this->simpanSkor($runBaru->id, $criterion->id, $baru->id, 5);

        $analysis = app(DssAnalysisService::class)->analyze();

        $this->assertSame($baru->id, $analysis['vikor']['best_id']);
        $this->assertSame($baru->id, $analysis['vikor']['ranking'][0]);

        $suppliers = app(DssAnalysisService::class)->suppliersWithScores();
        $baruRow = collect($suppliers)->firstWhere('supplier_id', $baru->id);

        $this->assertEquals(5, $baruRow['scores'][0]['score']);
    }

    private function simpanSkor(int $runId, int $criterionId, int $supplierId, int $score): void
    {
        DssSupplierScore::create([
            'dss_run_id' => $runId,
            'dss_criterion_id' => $criterionId,
            'supplier_id' => $supplierId,
            'score' => $score,
            'weight' => 1,
        ]);
    }
}
