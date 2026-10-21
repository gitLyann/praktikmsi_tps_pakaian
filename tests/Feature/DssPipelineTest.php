<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssCriterionComparison;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use App\Services\AhpService;
use App\Services\VikorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur lengkap AHP -> VIKOR memakai data di database.
 */
class DssPipelineTest extends TestCase
{
    use RefreshDatabase;

    private AhpService $ahp;

    private VikorService $vikor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ahp = new AhpService;
        $this->vikor = new VikorService;
    }

    public function test_alur_ahp_lalu_vikor_memberi_peringkat_pemasok(): void
    {
        $harga = DssCriterion::factory()->cost()->create(['code' => 'harga', 'sort_order' => 1]);
        $kualitas = DssCriterion::factory()->benefit()->create(['code' => 'kualitas', 'sort_order' => 2]);

        $this->createComparisons($harga, $kualitas, 2);

        $criteria = DssCriterion::query()->get();

        // Bobot hasil AHP (kunci = id kriteria).
        $ahpResult = $this->ahp->analyze($criteria);
        $weights = $ahpResult['criteria_weights'];

        $this->assertEqualsWithDelta(2 / 3, $weights[$harga->id], 1e-4);
        $this->assertEqualsWithDelta(1 / 3, $weights[$kualitas->id], 1e-4);

        $run = DssRun::create([
            'name' => 'Run VIKOR Uji',
            'type' => 'vikor',
            'status' => 'completed',
            'criteria_count' => $ahpResult['criteria_count'],
            'lambda_max' => $ahpResult['lambda_max'],
            'ci' => $ahpResult['ci'],
            'cr' => $ahpResult['cr'],
            'ri' => $ahpResult['ri'],
            'is_consistent' => $ahpResult['is_consistent'],
        ]);

        $baik = Supplier::factory()->create(['name' => 'Pemasok Baik']);
        $sedang = Supplier::factory()->create(['name' => 'Pemasok Sedang']);
        $buruk = Supplier::factory()->create(['name' => 'Pemasok Buruk']);

        // Skor: harga (cost) 5 = paling mahal, kualitas (benefit) 5 = paling bagus.
        // Jadi pemasok terbaik: harga paling murah (1) dan kualitas tertinggi (5).
        $profiles = [
            [$baik, [1, 5]],
            [$sedang, [3, 3]],
            [$buruk, [5, 1]],
        ];

        foreach ($profiles as [$supplier, [$hargaScore, $kualitasScore]]) {
            foreach ([[$harga, $hargaScore], [$kualitas, $kualitasScore]] as [$criterion, $score]) {
                DssSupplierScore::create([
                    'dss_run_id' => $run->id,
                    'supplier_id' => $supplier->id,
                    'dss_criterion_id' => $criterion->id,
                    'score' => $score,
                    'weight' => $weights[$criterion->id],
                ]);
            }
        }

        $suppliers = Supplier::query()->whereIn('id', [$baik->id, $sedang->id, $buruk->id])->get();

        $vikorResult = $this->vikor->analyze($suppliers, $criteria, $weights);

        $this->assertSame($baik->id, $vikorResult['best_id']);
        $this->assertSame($baik->id, $vikorResult['ranking'][0]);
        $this->assertSame($buruk->id, $vikorResult['ranking'][2]);
        // VIKOR: pemasok yang unggul di semua kriteria tetap punya R tertinggi,
        // sehingga labelnya "Diterima" (bukan "Solusi Kompromi").
        $this->assertSame('Diterima', $vikorResult['best_classification']);
        $this->assertEqualsWithDelta(1.0, $vikorResult['scores'][$baik->id]['S'], 1e-6);
    }

    public function test_skor_tanpa_run_tidak_ikut_dihitung(): void
    {
        $criterion = DssCriterion::factory()->benefit()->create(['sort_order' => 1]);
        $supplier = Supplier::factory()->create();

        $result = $this->vikor->analyze(
            Supplier::query()->get(),
            DssCriterion::query()->get(),
            [$criterion->id => 1.0]
        );

        $this->assertSame([], $result['ranking']);
    }

    private function createComparisons(DssCriterion $a, DssCriterion $b, float $ratio): void
    {
        DssCriterionComparison::create([
            'criterion_a_id' => $a->id,
            'criterion_b_id' => $b->id,
            'value' => $ratio,
        ]);

        DssCriterionComparison::create([
            'criterion_a_id' => $b->id,
            'criterion_b_id' => $a->id,
            'value' => round(1 / $ratio, 4),
        ]);
    }
}
