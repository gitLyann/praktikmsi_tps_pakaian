<?php

namespace Tests\Unit;

use App\Services\VikorService;
use PHPUnit\Framework\TestCase;

class VikorServiceTest extends TestCase
{
    private VikorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VikorService;
    }

    public function test_perhitungan_manual_tiga_alternatif(): void
    {
        // Kriteria 1 = benefit (bobot 0,6), kriteria 2 = cost (bobot 0,4).
        // Normalisasi:
        //   C1 (max 5): A=1,0  B=0,6  C=0,2
        //   C2 (max 8, min 2): A=1,0  B=0,666667  C=0,0
        // S = 0,6*r1 + 0,4*r2 ; R = MAX(v_ij)
        // S^- = 0,12 ; R^+ = 0,6
        // Q = 0,5*(S^- - S) + 0,5*(R - R^+)
        $matrix = [
            'A' => [1 => 5, 2 => 2],
            'B' => [1 => 3, 2 => 4],
            'C' => [1 => 1, 2 => 8],
        ];

        $result = $this->service->calculate(
            $matrix,
            [1 => 0.6, 2 => 0.4],
            [1 => 'benefit', 2 => 'cost']
        );

        $this->assertEqualsWithDelta(1.0, $result['scores']['A']['S'], 1e-6);
        $this->assertEqualsWithDelta(0.6, $result['scores']['A']['R'], 1e-6);
        $this->assertEqualsWithDelta(-0.44, $result['scores']['A']['Q'], 1e-6);

        $this->assertEqualsWithDelta(0.6266667, $result['scores']['B']['S'], 1e-6);
        $this->assertEqualsWithDelta(0.36, $result['scores']['B']['R'], 1e-6);
        $this->assertEqualsWithDelta(-0.3733333, $result['scores']['B']['Q'], 1e-6);

        $this->assertEqualsWithDelta(0.12, $result['scores']['C']['S'], 1e-6);
        $this->assertEqualsWithDelta(0.12, $result['scores']['C']['R'], 1e-6);
        $this->assertEqualsWithDelta(-0.24, $result['scores']['C']['Q'], 1e-6);

        $this->assertSame(['A', 'B', 'C'], $result['ranking']);
        $this->assertSame('A', $result['best_id']);
        $this->assertEqualsWithDelta(0.5, $result['threshold'], 1e-9);
    }

    public function test_urutan_q_menaik_dan_peringkat_terbaik(): void
    {
        $matrix = [
            'A' => [1 => 5, 2 => 2],
            'B' => [1 => 3, 2 => 4],
            'C' => [1 => 1, 2 => 8],
        ];

        $result = $this->service->calculate($matrix, [1 => 0.6, 2 => 0.4], [1 => 'benefit', 2 => 'cost']);

        $qValues = array_values(array_map(fn ($score) => $score['Q'], $result['scores']));
        $sorted = $qValues;
        sort($sorted);

        $this->assertSame($sorted, $qValues);
        $this->assertSame(1, $result['scores']['A']['rank']);
        $this->assertTrue($result['scores']['A']['is_best']);
        $this->assertSame(2, $result['scores']['B']['rank']);
        $this->assertSame(3, $result['scores']['C']['rank']);
    }

    public function test_kriteria_biaya_dinormalisasi_terbalik(): void
    {
        $matrix = [
            'murah' => [1 => 10],
            'mahal' => [1 => 50],
        ];

        $result = $this->service->calculate($matrix, [1 => 1.0], [1 => 'cost']);

        $this->assertEqualsWithDelta(1.0, $result['normalized']['murah'][1], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['normalized']['mahal'][1], 1e-9);
        $this->assertSame(['murah', 'mahal'], $result['ranking']);
    }

    public function test_kriteria_benefit_dinormalisasi_membagi_max(): void
    {
        $matrix = [
            'baik' => [1 => 80],
            'sedang' => [1 => 40],
        ];

        $result = $this->service->calculate($matrix, [1 => 1.0], [1 => 'benefit']);

        $this->assertEqualsWithDelta(1.0, $result['normalized']['baik'][1], 1e-9);
        $this->assertEqualsWithDelta(0.5, $result['normalized']['sedang'][1], 1e-9);
    }

    public function test_nilai_kriteria_sama_tidak_membagi_nol(): void
    {
        $matrix = [
            'A' => [1 => 10, 2 => 5],
            'B' => [1 => 10, 2 => 5],
        ];

        $result = $this->service->calculate($matrix, [1 => 0.5, 2 => 0.5], [1 => 'benefit', 2 => 'cost']);

        $this->assertEqualsWithDelta(1.0, $result['normalized']['A'][1], 1e-9);
        $this->assertEqualsWithDelta(1.0, $result['normalized']['A'][2], 1e-9);
        $this->assertCount(2, $result['scores']);
    }

    public function test_bobot_dinormalisasi_otomatis(): void
    {
        $matrix = [
            'A' => [1 => 5],
            'B' => [1 => 1],
        ];

        $result = $this->service->calculate($matrix, [1 => 3.0], [1 => 'benefit']);

        // Bobot 3 dinormalisasi menjadi 1 sehingga S maksimal 1.
        $this->assertEqualsWithDelta(1.0, $result['scores']['A']['S'], 1e-9);
        $this->assertEqualsWithDelta(0.2, $result['scores']['B']['S'], 1e-9);
    }

    public function test_matriks_kosong_tidak_menghasilkan_error(): void
    {
        $result = $this->service->calculate([], [], []);

        $this->assertSame([], $result['ranking']);
        $this->assertNull($result['best_id']);
        $this->assertEqualsWithDelta(0.0, $result['threshold'], 1e-9);
    }

    public function test_klasifikasi_menyebut_solusi_kompromi_saat_utility_dan_regresi_unggul(): void
    {
        // Kriteria 1 benefit (0,6), kriteria 2 cost (0,4).
        // B unggul pada utility (S) sekaligus pada regresi (R) dibanding C.
        $matrix = [
            'A' => [1 => 5, 2 => 8],
            'B' => [1 => 3, 2 => 4],
            'C' => [1 => 1, 2 => 2],
        ];

        $result = $this->service->calculate($matrix, [1 => 0.6, 2 => 0.4], [1 => 'benefit', 2 => 'cost']);

        $this->assertSame('B', $result['best_id']);
        $this->assertSame('Solusi Kompromi', $result['best_classification']);
        $this->assertTrue($result['is_stable']);
    }

    public function test_klasifikasi_diterima_saat_hanya_utility_unggul(): void
    {
        // A unggul pada utility (S) tetapi regresinya (R) lebih besar dibanding B,
        // sehingga VIKOR menandainya "Diterima", bukan solusi kompromi.
        $matrix = [
            'A' => [1 => 10, 2 => 10],
            'B' => [1 => 8, 2 => 8],
            'C' => [1 => 1, 2 => 1],
        ];

        $result = $this->service->calculate($matrix, [1 => 0.5, 2 => 0.5], [1 => 'benefit', 2 => 'benefit']);

        $this->assertSame('A', $result['best_id']);
        $this->assertSame('Diterima', $result['best_classification']);
        $this->assertEqualsWithDelta(-0.45, $result['scores']['A']['Q'], 1e-6);
        $this->assertEqualsWithDelta(-0.40, $result['scores']['B']['Q'], 1e-6);
    }

    public function test_parameter_v_dapat_diubah(): void
    {
        $matrix = [
            'A' => [1 => 5],
            'B' => [1 => 1],
        ];

        $result = $this->service->calculate($matrix, [1 => 1.0], [1 => 'benefit'], 0.7);

        $this->assertEqualsWithDelta(0.7, $result['v'], 1e-9);
    }
}
