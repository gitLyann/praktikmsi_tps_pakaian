<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use App\Services\DssAnalysisService;
use Database\Seeders\DssScoreSeeder;
use Database\Seeders\DssSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssScoreSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_mengisi_skor_untuk_semua_pemasok_dan_kriteria(): void
    {
        $this->seed(DssSeeder::class);

        $this->seed(DssScoreSeeder::class);

        $supplierCount = Supplier::query()->count();
        $criterionCount = DssCriterion::query()->count();

        $this->assertSame($supplierCount * $criterionCount, DssSupplierScore::query()->count());

        // Skor harga memakai nilai mentah rupiah dari penawaran pemasok.
        $supplier = Supplier::query()->orderBy('id')->first();
        $criterion = DssCriterion::query()->where('code', 'harga')->first();

        $score = DssSupplierScore::query()
            ->where('supplier_id', $supplier->id)
            ->where('dss_criterion_id', $criterion->id)
            ->first();

        $this->assertNotNull($score);
        $this->assertGreaterThan(0, (float) $score->score);
        $this->assertGreaterThan(0, (float) $score->weight);
    }

    public function test_seeder_adalah_idempoten(): void
    {
        $this->seed(DssSeeder::class);

        $this->seed(DssScoreSeeder::class);
        $first = DssSupplierScore::query()->count();

        $this->seed(DssScoreSeeder::class);

        $this->assertSame($first, DssSupplierScore::query()->count());
        $this->assertSame(1, DssRun::query()->where('name', 'Penilaian Pemasok Demo')->count());
    }

    public function test_análisis_menghasilkan_ranking_vikor(): void
    {
        $this->seed(DssSeeder::class);
        $this->seed(DssScoreSeeder::class);

        $analysis = app(DssAnalysisService::class)->analyze();

        $this->assertCount(Supplier::query()->count(), $analysis['vikor']['scores']);
        $this->assertNotEmpty($analysis['vikor']['ranking']);
        $this->assertNotNull($analysis['vikor']['best_id']);
    }
}
