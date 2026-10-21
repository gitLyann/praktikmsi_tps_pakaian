<?php

namespace Database\Seeders;

use App\Models\DssCriterion;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use App\Services\AhpService;
use Illuminate\Database\Seeder;

/**
 * Seeder skor penilaian pemasok untuk demo analisis VIKOR.
 *
 * Tanpa skor, tabel `dss_supplier_scores` kosong sehingga ranking VIKOR di
 * halaman Analisis DSS tidak menampilkan apa pun. Seeder ini mengisi skor
 * nilai mentah tiap pemasok pada setiap kriteria:
 * - `harga`     -> rata-rata harga beli di penawaran pemasok (cost)
 * - `lead_time` -> lead time pengiriman pemasok (cost)
 * - `moq`       -> minimum order quantity pemasok (cost)
 * - `kualitas`, `reputasi`, `layanan` -> turunan rating pemasok (benefit)
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=DssScoreSeeder
 *
 * Idempoten: skor ditulis ulang pada run penilaian yang sama.
 */
class DssScoreSeeder extends Seeder
{
    /** Nama run penilaian yang dipakai data demo. */
    private const RUN_NAME = 'Penilaian Pemasok Demo';

    public function run(): void
    {
        $suppliers = Supplier::query()->with('productSuppliers')->orderBy('id')->get();
        $criteria = DssCriterion::query()->orderBy('sort_order')->get();

        if ($suppliers->isEmpty() || $criteria->isEmpty()) {
            $this->command?->warn('Pemasok atau kriteria belum ada, skor demo dilewati.');

            return;
        }

        $run = DssRun::updateOrCreate(
            ['name' => self::RUN_NAME, 'type' => 'vikor'],
            [
                'status' => 'draft',
                'criteria_count' => $criteria->count(),
            ]
        );

        $weights = app(AhpService::class)->analyze($criteria)['criteria_weights'];

        $saved = 0;

        foreach ($suppliers as $supplier) {
            $scores = $this->scoresFor($supplier);

            foreach ($criteria as $criterion) {
                DssSupplierScore::updateOrCreate(
                    [
                        'dss_run_id' => $run->id,
                        'supplier_id' => $supplier->id,
                        'dss_criterion_id' => $criterion->id,
                    ],
                    [
                        'score' => $scores[$criterion->code] ?? 0,
                        'weight' => $weights[$criterion->id] ?? 0,
                    ]
                );

                $saved++;
            }
        }

        $this->command?->info('Skor demo tersimpan: '.$saved.' baris pada run #'.$run->id.'.');
    }

    /**
     * Nilai mentah tiap kriteria untuk satu pemasok.
     *
     * @return array<string, float>
     */
    private function scoresFor(Supplier $supplier): array
    {
        $prices = $supplier->productSuppliers->pluck('price')->filter(fn ($price): bool => $price > 0);
        $averagePrice = $prices->isEmpty() ? 100000.0 : (float) $prices->avg();

        // Rating 0-5 dinormalisasi ke skala 0-100 untuk kriteria benefit.
        $rating = ((float) $supplier->rating / 5) * 100;

        return [
            'harga' => round($averagePrice, 2),
            'kualitas' => round(min(100, $rating + 5), 2),
            'lead_time' => (float) $supplier->lead_time_days,
            'reputasi' => round($rating, 2),
            'moq' => (float) $supplier->min_order_qty,
            'layanan' => round(max(0, $rating - 3), 2),
        ];
    }
}
