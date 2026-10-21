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

    /**
     * Data spesifikasi baku:
     * bobot [0.4, 0.3, 0.2, 0.1], tipe [cost, benefit, cost, benefit],
     * A=[100,4,7,90], B=[120,5,5,95], C=[90,3,10,80], v=0.5.
     */
    public function test_data_baku_sesuai_spesifikasi(): void
    {
        $result = $this->service->calculate(
            $this->matrixBaku(),
            [1 => 0.4, 2 => 0.3, 3 => 0.2, 4 => 0.1],
            [1 => 'cost', 2 => 'benefit', 3 => 'cost', 4 => 'benefit'],
            0.5
        );

        $this->assertEqualsWithDelta(0.3967, $result['scores']['A']['S'], 1e-4);
        $this->assertEqualsWithDelta(0.4000, $result['scores']['B']['S'], 1e-4);
        $this->assertEqualsWithDelta(0.6000, $result['scores']['C']['S'], 1e-4);

        $this->assertEqualsWithDelta(0.15, $result['scores']['A']['R'], 1e-4);
        $this->assertEqualsWithDelta(0.40, $result['scores']['B']['R'], 1e-4);
        $this->assertEqualsWithDelta(0.30, $result['scores']['C']['R'], 1e-4);

        $this->assertEqualsWithDelta(0.0000, $result['scores']['A']['Q'], 1e-4);
        $this->assertEqualsWithDelta(0.5082, $result['scores']['B']['Q'], 1e-4);
        $this->assertEqualsWithDelta(0.8000, $result['scores']['C']['Q'], 1e-4);

        $this->assertSame(['A', 'B', 'C'], $result['ranking']);
        $this->assertSame('A', $result['best_id']);
        $this->assertSame(1, $result['scores']['A']['rank']);
        $this->assertSame(2, $result['scores']['B']['rank']);
        $this->assertSame(3, $result['scores']['C']['rank']);

        // Q ternormalisasi berada pada rentang 0..1.
        $this->assertGreaterThanOrEqual(0.0, $result['scores']['A']['Q']);
        $this->assertLessThanOrEqual(1.0, $result['scores']['C']['Q']);

        // Ambang DQ = 1/(J-1) = 1/(3-1) = 0,5.
        $this->assertEqualsWithDelta(0.5, $result['threshold'], 1e-9);

        // C1: Q(2) - Q(1) = 0,5082 >= 0,5 -> keunggulan dapat diterima.
        // C2: A juga terbaik menurut S maupun R -> stabilitas dapat diterima.
        $this->assertTrue($result['has_advantage']);
        $this->assertTrue($result['is_stable']);
        $this->assertSame(VikorService::CLASSIFICATION_SINGLE, $result['best_classification']);
        $this->assertSame(['A'], $result['compromise_set']);
        $this->assertSame(VikorService::CLASSIFICATION_SINGLE, $result['scores']['A']['classification']);
    }

    /**
     * Suku ternormalisasi per kriteria harus mengikuti rumus baku:
     * d_ij = w_j * (f*_j - f_ij) / (f*_j - f-_j).
     */
    public function test_suku_ternormalisasi_mengikuti_rumus_baku(): void
    {
        $result = $this->service->calculate(
            $this->matrixBaku(),
            [1 => 0.4, 2 => 0.3, 3 => 0.2, 4 => 0.1],
            [1 => 'cost', 2 => 'benefit', 3 => 'cost', 4 => 'benefit'],
            0.5
        );

        // Kriteria 1 (cost): f*=90 (C), f-=120 (B) -> A: 0,4*(90-100)/(90-120) = 0,133333
        $this->assertEqualsWithDelta(0.133333, $result['normalized']['A'][1], 1e-6);
        $this->assertEqualsWithDelta(0.400000, $result['normalized']['B'][1], 1e-6);
        $this->assertEqualsWithDelta(0.0, $result['normalized']['C'][1], 1e-6);

        // Kriteria 2 (benefit): f*=5 (B), f-=3 (C) -> A: 0,3*(5-4)/(5-3) = 0,15
        $this->assertEqualsWithDelta(0.150000, $result['normalized']['A'][2], 1e-6);
        $this->assertEqualsWithDelta(0.0, $result['normalized']['B'][2], 1e-6);
        $this->assertEqualsWithDelta(0.300000, $result['normalized']['C'][2], 1e-6);

        // Kriteria 3 (cost): f*=5 (B), f-=10 (C) -> A: 0,2*(5-7)/(5-10) = 0,08
        $this->assertEqualsWithDelta(0.080000, $result['normalized']['A'][3], 1e-6);
        $this->assertEqualsWithDelta(0.0, $result['normalized']['B'][3], 1e-6);
        $this->assertEqualsWithDelta(0.200000, $result['normalized']['C'][3], 1e-6);

        // Kriteria 4 (benefit): f*=95 (B), f-=80 (C) -> A: 0,1*(95-90)/(95-80) = 0,033333
        $this->assertEqualsWithDelta(0.033333, $result['normalized']['A'][4], 1e-6);
        $this->assertEqualsWithDelta(0.0, $result['normalized']['B'][4], 1e-6);
        $this->assertEqualsWithDelta(0.100000, $result['normalized']['C'][4], 1e-6);
    }

    public function test_perhitungan_manual_tiga_alternatif(): void
    {
        // Kriteria 1 = benefit (0,6), kriteria 2 = cost (0,4).
        // A=[5,2], B=[3,4], C=[1,8] -> S = 0 / 0,433333 / 1 dan R = 0 / 0,3 / 0,6
        $result = $this->service->calculate(
            [
                'A' => [1 => 5, 2 => 2],
                'B' => [1 => 3, 2 => 4],
                'C' => [1 => 1, 2 => 8],
            ],
            [1 => 0.6, 2 => 0.4],
            [1 => 'benefit', 2 => 'cost']
        );

        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['S'], 1e-6);
        $this->assertEqualsWithDelta(0.433333, $result['scores']['B']['S'], 1e-6);
        $this->assertEqualsWithDelta(1.0, $result['scores']['C']['S'], 1e-6);

        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['R'], 1e-6);
        $this->assertEqualsWithDelta(0.3, $result['scores']['B']['R'], 1e-6);
        $this->assertEqualsWithDelta(0.6, $result['scores']['C']['R'], 1e-6);

        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['Q'], 1e-6);
        $this->assertEqualsWithDelta(0.4666667, $result['scores']['B']['Q'], 1e-6);
        $this->assertEqualsWithDelta(1.0, $result['scores']['C']['Q'], 1e-6);

        $this->assertSame(['A', 'B', 'C'], $result['ranking']);
        $this->assertSame('A', $result['best_id']);
        $this->assertEqualsWithDelta(0.5, $result['threshold'], 1e-9);
    }

    public function test_urutan_q_menaik_dan_peringkat_terbaik(): void
    {
        $result = $this->service->calculate(
            [
                'A' => [1 => 5, 2 => 2],
                'B' => [1 => 3, 2 => 4],
                'C' => [1 => 1, 2 => 8],
            ],
            [1 => 0.6, 2 => 0.4],
            [1 => 'benefit', 2 => 'cost']
        );

        $qValues = array_values(array_map(fn ($score) => $score['Q'], $result['scores']));
        $sorted = $qValues;
        sort($sorted);

        $this->assertSame($sorted, $qValues);
        $this->assertSame(1, $result['scores']['A']['rank']);
        $this->assertTrue($result['scores']['A']['is_best']);
        $this->assertSame(2, $result['scores']['B']['rank']);
        $this->assertSame(3, $result['scores']['C']['rank']);
    }

    /**
     * Alternatif dominan (terbaik di semua kriteria) harus ber-S = 0 dan R = 0.
     */
    public function test_alternatif_dominan_memiliki_s_dan_r_nol(): void
    {
        $result = $this->service->calculate(
            [
                'D' => [1 => 10, 2 => 90],
                'E' => [1 => 20, 2 => 80],
                'F' => [1 => 30, 2 => 70],
            ],
            [1 => 0.5, 2 => 0.5],
            [1 => 'cost', 2 => 'benefit']
        );

        $this->assertEqualsWithDelta(0.0, $result['scores']['D']['S'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['scores']['D']['R'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['scores']['D']['Q'], 1e-9);
        $this->assertSame(1, $result['scores']['D']['rank']);
        $this->assertTrue($result['scores']['D']['is_best']);
        $this->assertSame('D', $result['best_id']);

        // Alternatif non-dominan tetap punya penyesuaian positif.
        $this->assertEqualsWithDelta(0.5, $result['scores']['E']['S'], 1e-9);
        $this->assertEqualsWithDelta(0.25, $result['scores']['E']['R'], 1e-9);
        $this->assertEqualsWithDelta(1.0, $result['scores']['F']['S'], 1e-9);
        $this->assertEqualsWithDelta(0.5, $result['scores']['F']['R'], 1e-9);

        $this->assertSame(VikorService::CLASSIFICATION_SINGLE, $result['best_classification']);
    }

    public function test_kriteria_biaya_menghasilkan_penyesuaian_nol_bagi_alternatif_terbaik(): void
    {
        $result = $this->service->calculate(
            [
                'murah' => [1 => 10],
                'mahal' => [1 => 50],
            ],
            [1 => 1.0],
            [1 => 'cost']
        );

        // Cost: (f* - f_i)/(f* - f-) = (10 - x)/(10 - 50)
        $this->assertEqualsWithDelta(0.0, $result['normalized']['murah'][1], 1e-9);
        $this->assertEqualsWithDelta(1.0, $result['normalized']['mahal'][1], 1e-9);
        $this->assertSame(['murah', 'mahal'], $result['ranking']);
        $this->assertEqualsWithDelta(0.0, $result['scores']['murah']['Q'], 1e-9);
        $this->assertEqualsWithDelta(1.0, $result['scores']['mahal']['Q'], 1e-9);
    }

    public function test_kriteria_benefit_menghasilkan_penyesuaian_nol_bagi_alternatif_terbaik(): void
    {
        $result = $this->service->calculate(
            [
                'baik' => [1 => 80],
                'sedang' => [1 => 40],
            ],
            [1 => 1.0],
            [1 => 'benefit']
        );

        // Benefit: (f* - f_i)/(f* - f-) = (80 - x)/(80 - 40)
        $this->assertEqualsWithDelta(0.0, $result['normalized']['baik'][1], 1e-9);
        $this->assertEqualsWithDelta(1.0, $result['normalized']['sedang'][1], 1e-9);
        $this->assertSame(['baik', 'sedang'], $result['ranking']);
    }

    public function test_nilai_kriteria_sama_tidak_membagi_nol(): void
    {
        $result = $this->service->calculate(
            [
                'A' => [1 => 10, 2 => 5],
                'B' => [1 => 10, 2 => 5],
            ],
            [1 => 0.5, 2 => 0.5],
            [1 => 'benefit', 2 => 'cost']
        );

        $this->assertEqualsWithDelta(0.0, $result['normalized']['A'][1], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['normalized']['A'][2], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['S'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['R'], 1e-9);
        $this->assertCount(2, $result['scores']);
    }

    public function test_bobot_dinormalisasi_otomatis(): void
    {
        $result = $this->service->calculate(
            [
                'A' => [1 => 5],
                'B' => [1 => 1],
            ],
            [1 => 3.0],
            [1 => 'benefit']
        );

        // Bobot 3 dinormalisasi menjadi 1 sehingga penyesuaian maksimal 1.
        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['S'], 1e-9);
        $this->assertEqualsWithDelta(1.0, $result['scores']['B']['S'], 1e-9);
    }

    public function test_matriks_kosong_tidak_menghasilkan_error(): void
    {
        $result = $this->service->calculate([], [], []);

        $this->assertSame([], $result['ranking']);
        $this->assertNull($result['best_id']);
        $this->assertEqualsWithDelta(0.0, $result['threshold'], 1e-9);
        $this->assertSame([], $result['compromise_set']);
    }

    public function test_satu_alternatif_berhasil_jadi_solusi_tunggal(): void
    {
        $result = $this->service->calculate(
            ['A' => [1 => 5]],
            [1 => 1.0],
            [1 => 'benefit']
        );

        $this->assertSame(['A'], $result['ranking']);
        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['S'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['R'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['scores']['A']['Q'], 1e-9);
        $this->assertTrue($result['has_advantage']);
        $this->assertTrue($result['is_stable']);
        $this->assertSame(VikorService::CLASSIFICATION_SINGLE, $result['best_classification']);
    }

    /**
     * C1 gagal saat selisih Q(2) - Q(1) lebih kecil dari DQ = 1/(J-1) = 0,5.
     */
    public function test_klasifikasi_gagal_keunggulan_menghasilkan_kompromi_semua_alternatif(): void
    {
        // Selisih Q kedua - pertama = 0,4667 < 1/(J-1) = 0,5 -> tidak advantageous.
        $result = $this->service->calculate(
            [
                'A' => [1 => 5, 2 => 2],
                'B' => [1 => 3, 2 => 4],
                'C' => [1 => 1, 2 => 8],
            ],
            [1 => 0.6, 2 => 0.4],
            [1 => 'benefit', 2 => 'cost']
        );

        $this->assertSame('A', $result['best_id']);
        $this->assertFalse($result['has_advantage']);
        $this->assertTrue($result['is_stable']);
        $this->assertSame(VikorService::CLASSIFICATION_COMPROMISE_SET, $result['best_classification']);

        // Set kompromi = alternatif dengan Q(m) - Q(1) < DQ (A dan B, bukan C).
        $this->assertSame(['A', 'B'], $result['compromise_set']);
        $this->assertSame(VikorService::CLASSIFICATION_COMPROMISE, $result['scores']['B']['classification']);
        $this->assertSame(VikorService::CLASSIFICATION_ALTERNATIVE, $result['scores']['C']['classification']);
    }

    /**
     * Klasifikasi tidak pernah memakai label "Diterima"/"Tidak Diterima".
     */
    public function test_label_klasifikasi_tidak_memakai_diterima(): void
    {
        $result = $this->service->calculate(
            $this->matrixBaku(),
            [1 => 0.4, 2 => 0.3, 3 => 0.2, 4 => 0.1],
            [1 => 'cost', 2 => 'benefit', 3 => 'cost', 4 => 'benefit'],
            0.5
        );

        $labels = array_column($result['scores'], 'classification');
        $labels[] = $result['best_classification'];

        foreach ($labels as $label) {
            $this->assertStringNotContainsString('Diterima', $label);
        }
    }

    /**
     * Saat Q sama, urutan ditentukan oleh S terkecil lalu R terkecil.
     */
    public function test_tie_break_q_sama_diurutkan_s_kemudian_r(): void
    {
        // Z dan W sama-sama Q = 0,5; W lebih kecil pada S (0,4 < 0,5) sehingga mendahului Z.
        $result = $this->service->calculate(
            [
                'X' => [1 => 10, 2 => 0, 3 => 0],
                'Y' => [1 => 0, 2 => 10, 3 => 10],
                'Z' => [1 => 5, 2 => 5, 3 => 5],
                'W' => [1 => 8, 2 => 10, 3 => 0],
            ],
            [1 => 0.5, 2 => 0.3, 3 => 0.2],
            [1 => 'benefit', 2 => 'cost', 3 => 'cost']
        );

        $this->assertEqualsWithDelta(0.5, $result['scores']['Z']['Q'], 1e-9);
        $this->assertEqualsWithDelta(0.5, $result['scores']['W']['Q'], 1e-9);
        $this->assertSame(['X', 'W', 'Z', 'Y'], $result['ranking']);
        $this->assertSame(2, $result['scores']['W']['rank']);
        $this->assertSame(3, $result['scores']['Z']['rank']);
    }

    public function test_q_selalu_berada_pada_rentang_nol_sampai_satu(): void
    {
        foreach ([0.0, 0.25, 0.5, 0.75, 1.0] as $v) {
            $result = $this->service->calculate(
                $this->matrixBaku(),
                [1 => 0.4, 2 => 0.3, 3 => 0.2, 4 => 0.1],
                [1 => 'cost', 2 => 'benefit', 3 => 'cost', 4 => 'benefit'],
                $v
            );

            foreach ($result['scores'] as $score) {
                $this->assertGreaterThanOrEqual(0.0, $score['Q']);
                $this->assertLessThanOrEqual(1.0, $score['Q']);
            }
        }
    }

    public function test_parameter_v_dapat_diubah(): void
    {
        $result = $this->service->calculate(
            [
                'A' => [1 => 5],
                'B' => [1 => 1],
            ],
            [1 => 1.0],
            [1 => 'benefit'],
            0.7
        );

        $this->assertEqualsWithDelta(0.7, $result['v'], 1e-9);
        // Q ternormalisasi tetap berada di rentang 0..1 untuk nilai v berapa pun.
        $this->assertGreaterThanOrEqual(0.0, $result['scores']['A']['Q']);
        $this->assertLessThanOrEqual(1.0, $result['scores']['B']['Q']);
    }

    public function test_parameter_v_di_luar_rentang_dijepit(): void
    {
        $matrix = [
            'A' => [1 => 5],
            'B' => [1 => 1],
        ];

        $result = $this->service->calculate($matrix, [1 => 1.0], [1 => 'benefit'], 1.5);
        $this->assertEqualsWithDelta(1.0, $result['v'], 1e-9);
        $this->assertLessThanOrEqual(1.0, $result['scores']['B']['Q']);

        $result = $this->service->calculate($matrix, [1 => 1.0], [1 => 'benefit'], -0.5);
        $this->assertEqualsWithDelta(0.0, $result['v'], 1e-9);
        $this->assertGreaterThanOrEqual(0.0, $result['scores']['A']['Q']);
    }

    /**
     * @return array<string, array<int, float>>
     */
    private function matrixBaku(): array
    {
        return [
            'A' => [1 => 100, 2 => 4, 3 => 7, 4 => 90],
            'B' => [1 => 120, 2 => 5, 3 => 5, 4 => 95],
            'C' => [1 => 90, 2 => 3, 3 => 10, 4 => 80],
        ];
    }
}
