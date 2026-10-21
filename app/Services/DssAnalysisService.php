<?php

namespace App\Services;

use App\Models\DssCriterion;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Orkestrasi analisis DSS: AHP -> VIKOR -> Rekomendasi Restock.
 *
 * Service ini merangkai ketiga service perhitungan yang sudah ada menjadi satu
 * alur yang bisa dipakai controller admin (menjalankan & membaca analisis) dan
 * controller staff (menyimpan skor penilaian pemasok).
 */
class DssAnalysisService
{
    public function __construct(
        private readonly AhpService $ahpService,
        private readonly VikorService $vikorService,
        private readonly RestockRecommendationService $restockService
    ) {}

    /**
     * Kriteria penilaian yang aktif, urut sesuai urutan penilaian.
     *
     * @return Collection<int, DssCriterion>
     */
    public function criteria(): Collection
    {
        return DssCriterion::query()->orderBy('sort_order')->get();
    }

    /**
     * Menjalankan seluruh analisis DSS.
     *
     * @return array{ahp: array, vikor: array, restock: array}
     */
    public function analyze(): array
    {
        $criteria = $this->criteria();
        $ahp = $this->ahpService->analyze($criteria);
        $weights = $ahp['criteria_weights'];

        $suppliers = Supplier::query()->orderBy('name')->get();
        $vikor = $this->vikorService->analyze($suppliers, $criteria, $weights);

        $restock = $this->restockService->analyze(
            null,
            RestockRecommendationService::ANALYSIS_DAYS,
            $this->supplierRanking($vikor)
        );

        return [
            'ahp' => $this->formatAhp($ahp, $criteria),
            'vikor' => $this->formatVikor($vikor, $suppliers),
            'restock' => $this->formatRestock($restock),
        ];
    }

    /**
     * Menyimpan hasil analisis sebagai riwayat run.
     *
     * @param  array{ahp: array, vikor: array, restock: array}  $analysis
     */
    public function storeRun(User $user, array $analysis): DssRun
    {
        $ahp = $analysis['ahp'];

        return DssRun::create([
            'name' => 'Analisis DSS - '.now()->format('d/m/Y H:i'),
            'type' => 'vikor',
            'status' => 'completed',
            'created_by' => $user->id,
            'criteria_count' => $ahp['criteria_count'],
            'lambda_max' => $ahp['lambda_max'],
            'ci' => $ahp['ci'],
            'cr' => $ahp['cr'],
            'ri' => $ahp['ri'],
            'is_consistent' => $ahp['is_consistent'],
            'payload' => $analysis,
        ]);
    }

    /**
     * Menyimpan skor penilaian pemasok pada run penilaian aktif.
     *
     * Run penilaian dibuat otomatis bila belum ada, sehingga staff bisa langsung
     * menginput skor tanpa perlu menyiapkan run lebih dulu.
     *
     * @param  array<int, array{supplier_id: int, criterion_id: int, score: float}>  $scores
     * @return array{run: DssRun, saved: int}
     */
    public function saveScores(array $scores, User $user): array
    {
        $criteria = $this->criteria();
        $weights = $this->ahpService->analyze($criteria)['criteria_weights'];

        $run = DssRun::query()
            ->where('type', 'vikor')
            ->where('status', 'draft')
            ->first();

        if ($run === null) {
            $run = DssRun::create([
                'name' => 'Penilaian Pemasok',
                'type' => 'vikor',
                'status' => 'draft',
                'created_by' => $user->id,
                'criteria_count' => $criteria->count(),
            ]);
        }

        $saved = 0;

        foreach ($scores as $score) {
            DssSupplierScore::updateOrCreate(
                [
                    'dss_run_id' => $run->id,
                    'supplier_id' => $score['supplier_id'],
                    'dss_criterion_id' => $score['criterion_id'],
                ],
                [
                    'score' => (float) $score['score'],
                    'weight' => (float) ($weights[$score['criterion_id']] ?? 0.0),
                ]
            );

            $saved++;
        }

        return ['run' => $run, 'saved' => $saved];
    }

    /**
     * Kriteria penilaian beserta bobot AHP-nya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function criteriaWithWeights(): array
    {
        $criteria = $this->criteria();
        $weights = $this->ahpService->analyze($criteria)['criteria_weights'];

        $result = [];

        foreach ($criteria as $criterion) {
            $result[] = [
                'criterion_id' => $criterion->id,
                'code' => $criterion->code,
                'name' => $criterion->name,
                'type' => $criterion->type,
                'weight' => round((float) ($weights[$criterion->id] ?? 0.0), 6),
            ];
        }

        return $result;
    }

    /**
     * Run penilaian yang sedang dipakai staff (status draft).
     */
    public function activeScoringRun(): ?DssRun
    {
        return DssRun::query()
            ->where('type', 'vikor')
            ->where('status', 'draft')
            ->first();
    }

    /**
     * Daftar pemasok beserta skor penilaian per kriteria.
     *
     * @return array<int, array<string, mixed>>
     */
    public function suppliersWithScores(): array
    {
        $criteria = $this->criteria();
        $suppliers = Supplier::query()->orderBy('name')->get();

        $scores = DssSupplierScore::query()
            ->whereIn('dss_criterion_id', $criteria->pluck('id'))
            ->get();

        $lookup = [];
        foreach ($scores as $score) {
            $lookup[$score->supplier_id][$score->dss_criterion_id] = (float) $score->score;
        }

        $result = [];

        foreach ($suppliers as $supplier) {
            $row = [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'code' => $supplier->code,
                'lead_time_days' => $supplier->lead_time_days,
                'min_order_qty' => $supplier->min_order_qty,
                'rating' => $supplier->rating,
                'scores' => [],
            ];

            foreach ($criteria as $criterion) {
                $row['scores'][] = [
                    'criterion_id' => $criterion->id,
                    'code' => $criterion->code,
                    'name' => $criterion->name,
                    'type' => $criterion->type,
                    'score' => $lookup[$supplier->id][$criterion->id] ?? null,
                ];
            }

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Memetakan id pemasok ke peringkat VIKOR (1 = terbaik).
     *
     * @param  array{ranking: array<int, int>}  $vikor
     * @return array<int, int>
     */
    private function supplierRanking(array $vikor): array
    {
        $ranking = [];

        foreach ($vikor['ranking'] as $index => $supplierId) {
            $ranking[$supplierId] = $index + 1;
        }

        return $ranking;
    }

    /**
     * Menyusun ringkasan AHP beserta bobot tiap kriteria.
     *
     * @param  array{criteria_count: int, lambda_max: float, ci: float, ri: float, cr: float, is_consistent: bool, criteria_weights: array<int, float>}  $ahp
     * @param  Collection<int, DssCriterion>  $criteria
     * @return array<string, mixed>
     */
    private function formatAhp(array $ahp, Collection $criteria): array
    {
        $weights = [];

        foreach ($criteria as $criterion) {
            $weights[] = [
                'criterion_id' => $criterion->id,
                'code' => $criterion->code,
                'name' => $criterion->name,
                'type' => $criterion->type,
                'weight' => round((float) ($ahp['criteria_weights'][$criterion->id] ?? 0.0), 6),
            ];
        }

        return [
            'criteria_count' => $ahp['criteria_count'],
            'lambda_max' => round((float) $ahp['lambda_max'], 6),
            'ci' => round((float) $ahp['ci'], 6),
            'ri' => round((float) $ahp['ri'], 6),
            'cr' => round((float) $ahp['cr'], 6),
            'is_consistent' => (bool) $ahp['is_consistent'],
            'weights' => $weights,
        ];
    }

    /**
     * Menyusun ringkasan VIKOR beserta peringkat tiap pemasok.
     *
     * @param  array<string, mixed>  $vikor
     * @param  Collection<int, Supplier>  $suppliers
     * @return array<string, mixed>
     */
    private function formatVikor(array $vikor, Collection $suppliers): array
    {
        $names = $suppliers->pluck('name', 'id');

        $scores = [];

        foreach ($vikor['scores'] as $supplierId => $score) {
            $scores[] = [
                'supplier_id' => $supplierId,
                'supplier_name' => $names[$supplierId] ?? 'Pemasok #'.$supplierId,
                'rank' => $score['rank'],
                'S' => $score['S'],
                'R' => $score['R'],
                'Q' => $score['Q'],
                'classification' => $score['classification'],
                'is_best' => $score['is_best'],
            ];
        }

        return [
            'v' => $vikor['v'],
            'threshold' => $vikor['threshold'],
            'ranking' => $vikor['ranking'],
            'best_id' => $vikor['best_id'],
            'best_classification' => $vikor['best_classification'],
            'compromise_set' => $vikor['compromise_set'],
            'has_advantage' => $vikor['has_advantage'],
            'is_stable' => $vikor['is_stable'],
            'scores' => $scores,
        ];
    }

    /**
     * Menyusun ringkasan rekomendasi restock.
     *
     * @param  array{items: Collection<int, array<string, mixed>>, summary: array<string, mixed>}  $restock
     * @return array<string, mixed>
     */
    private function formatRestock(array $restock): array
    {
        $items = [];

        foreach ($restock['items'] as $item) {
            $items[] = [
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'supplier_id' => $item['supplier']?->id,
                'supplier_name' => $item['supplier']?->name,
                'current_stock' => $item['current_stock'],
                'reorder_point' => $item['reorder_point'],
                'recommended_qty' => $item['recommended_qty'],
                'estimated_cost' => $item['estimated_cost'],
                'needs_restock' => $item['needs_restock'],
            ];
        }

        return array_merge($restock['summary'], [
            'generated_at' => $restock['generated_at'],
            'days' => $restock['days'],
            'items' => $items,
        ]);
    }
}
