<?php

namespace Tests\Unit;

use App\Services\AhpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AhpServiceTest extends TestCase
{
    private AhpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AhpService;
    }

    public function test_matriks_saaty_klasik_memberi_bobot_sep_tujuh(): void
    {
        $matrix = [
            [1, 2, 4],
            [0.5, 1, 2],
            [0.25, 0.5, 1],
        ];

        $result = $this->service->calculate($matrix);

        $this->assertEqualsWithDelta([4 / 7, 2 / 7, 1 / 7], array_values($result['weights']), 1e-9);
        $this->assertEqualsWithDelta(3.0, $result['lambda_max'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['ci'], 1e-9);
        $this->assertEqualsWithDelta(0.58, $result['ri'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['cr'], 1e-9);
        $this->assertTrue($result['is_consistent']);
        $this->assertSame(3, $result['criteria_count']);
    }

    public function test_bobot_selalu_jumlahnya_satu(): void
    {
        // Matriks konsisten dengan bobot proporsional 8 : 4 : 2 : 1.
        $matrix = [
            [1, 2, 4, 8],
            [0.5, 1, 2, 4],
            [0.25, 0.5, 1, 2],
            [0.125, 0.25, 0.5, 1],
        ];

        $result = $this->service->calculate($matrix);

        $this->assertEqualsWithDelta(1.0, array_sum($result['weights']), 1e-9);
        $this->assertEqualsWithDelta(4.0, $result['lambda_max'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $result['cr'], 1e-9);
        $this->assertEqualsWithDelta([8 / 15, 4 / 15, 2 / 15, 1 / 15], array_values($result['weights']), 1e-9);
    }

    public function test_matriks_tidak_konsisten_ditandai_tidak_konsisten(): void
    {
        // Matriks yang sengaja dibuat bertentangan antar kolom.
        $matrix = [
            [1, 9, 1],
            [1 / 9, 1, 9],
            [1, 1 / 9, 1],
        ];

        $result = $this->service->calculate($matrix);

        $this->assertGreaterThan(AhpService::CONSISTENCY_THRESHOLD, $result['cr']);
        $this->assertFalse($result['is_consistent']);
    }

    public function test_satu_kriteria_menghasilkan_bobot_satu_dan_cr_nol(): void
    {
        $result = $this->service->calculate([[1.0]]);

        $this->assertSame([1.0], $result['weights']);
        $this->assertEqualsWithDelta(0.0, $result['cr'], 1e-9);
        $this->assertTrue($result['is_consistent']);
    }

    public function test_matri_kosong_tidak_menghasilkan_error(): void
    {
        $result = $this->service->calculate([]);

        $this->assertSame([], $result['weights']);
        $this->assertSame(0, $result['criteria_count']);
        $this->assertTrue($result['is_consistent']);
    }

    public function test_matri_bujur_sama_dengan_panjang_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->calculate([
            [1, 2, 3],
            [1, 1, 1],
        ]);
    }

    public function test_random_index_saaty_terdaftar_untuk_orde_1_sampai_10(): void
    {
        $this->assertSame(0.58, AhpService::RANDOM_INDEX[3]);
        $this->assertSame(1.24, AhpService::RANDOM_INDEX[6]);
        $this->assertSame(1.49, AhpService::RANDOM_INDEX[10]);
    }
}
