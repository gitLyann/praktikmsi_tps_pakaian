<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssCriterionComparison;
use App\Services\AhpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AhpAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private AhpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AhpService;
    }

    public function test_analyze_dari_database_menghasilkan_bobot_per_kriteria(): void
    {
        $harga = DssCriterion::factory()->cost()->create(['code' => 'C1', 'name' => 'Harga', 'sort_order' => 1]);
        $kualitas = DssCriterion::factory()->benefit()->create(['code' => 'C2', 'name' => 'Kualitas', 'sort_order' => 2]);
        $leadTime = DssCriterion::factory()->cost()->create(['code' => 'C3', 'name' => 'Lead Time', 'sort_order' => 3]);

        // Matriks konsisten dengan bobot 4 : 2 : 1.
        $pairs = [
            [$harga, $kualitas, 2],
            [$kualitas, $harga, 0.5],
            [$harga, $leadTime, 4],
            [$leadTime, $harga, 0.25],
            [$kualitas, $leadTime, 2],
            [$leadTime, $kualitas, 0.5],
        ];

        foreach ($pairs as [$a, $b, $value]) {
            DssCriterionComparison::create([
                'criterion_a_id' => $a->id,
                'criterion_b_id' => $b->id,
                'value' => $value,
            ]);
        }

        $result = $this->service->analyze(DssCriterion::query()->get());

        $this->assertSame(3, $result['criteria_count']);
        $this->assertEqualsWithDelta(4 / 7, $result['criteria_weights'][$harga->id], 1e-4);
        $this->assertEqualsWithDelta(2 / 7, $result['criteria_weights'][$kualitas->id], 1e-4);
        $this->assertEqualsWithDelta(1 / 7, $result['criteria_weights'][$leadTime->id], 1e-4);
        $this->assertEqualsWithDelta(3.0, $result['lambda_max'], 1e-4);
        $this->assertEqualsWithDelta(0.0, $result['cr'], 1e-4);
        $this->assertTrue($result['is_consistent']);
    }

    public function test_kriteria_tanpa_perbandingan_dianggap_sama_penting(): void
    {
        $satu = DssCriterion::factory()->benefit()->create(['sort_order' => 1]);
        $dua = DssCriterion::factory()->benefit()->create(['sort_order' => 2]);

        $result = $this->service->analyze(DssCriterion::query()->get());

        $this->assertEqualsWithDelta(0.5, $result['criteria_weights'][$satu->id], 1e-6);
        $this->assertEqualsWithDelta(0.5, $result['criteria_weights'][$dua->id], 1e-6);
        $this->assertTrue($result['is_consistent']);
    }

    public function test_urutan_kriteria_mengikuti_sort_order(): void
    {
        $kedua = DssCriterion::factory()->create(['sort_order' => 2]);
        $pertama = DssCriterion::factory()->create(['sort_order' => 1]);

        DssCriterionComparison::create([
            'criterion_a_id' => $pertama->id,
            'criterion_b_id' => $kedua->id,
            'value' => 2,
        ]);

        DssCriterionComparison::create([
            'criterion_a_id' => $kedua->id,
            'criterion_b_id' => $pertama->id,
            'value' => 0.5,
        ]);

        $result = $this->service->analyze(DssCriterion::query()->get());

        $this->assertGreaterThan(
            $result['criteria_weights'][$kedua->id],
            $result['criteria_weights'][$pertama->id]
        );
    }

    public function test_tanpa_kriteria_hasil_kosong_dan_konsisten(): void
    {
        $result = $this->service->analyze(DssCriterion::query()->get());

        $this->assertSame(0, $result['criteria_count']);
        $this->assertSame([], $result['criteria_weights']);
        $this->assertTrue($result['is_consistent']);
    }
}
