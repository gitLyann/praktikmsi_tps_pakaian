<?php

namespace App\Services;

use App\Models\DssCriterion;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Perhitungan VIKOR (VIse Kriterijum Optimizacije) untuk peringkat alternatif.
 *
 * Langkah:
 * 1. Normalisasi matriks keputusan (benefit: x/max, cost: (max-x)/(max-min)).
 * 2. Pembobotan dengan bobot kriteria (hasil AHP).
 * 3. Ukuran utility S_i = SUM(v_ij) dan ukuran regresi R_i = MAX(v_ij / r_j+).
 * 4. Indeks kompromi Q_i = v * (S- - S_i) + (1 - v) * (R_i - R+).
 * 5. Klasifikasi: solusi kompromi / diterima / tidak diterima.
 */
class VikorService
{
    /**
     * Parameter kompromi (v). Nilai 0.5 berarti bobot S dan R seimbang.
     */
    public const DEFAULT_V = 0.5;

    /**
     * Menghitung peringkat VIKOR.
     *
     * @param  array<int|string, array<int|string, float>>  $decisionMatrix  [alternatif][kriteria] = nilai
     * @param  array<int|string, float>  $weights  [kriteria] = bobot (tidak harus berjumlah 1)
     * @param  array<int|string, string>  $criterionTypes  [kriteria] = 'benefit' | 'cost'
     * @return array{v: float, criteria_ids: array, normalized: array, scores: array, ranking: array, threshold: float, best_id: mixed, best_classification: string, has_advantage: bool, is_stable: bool}
     */
    public function calculate(
        array $decisionMatrix,
        array $weights,
        array $criterionTypes,
        ?float $v = null
    ): array {
        $v = $v ?? self::DEFAULT_V;

        $criteriaIds = array_keys($weights);

        if ($criteriaIds === [] || $decisionMatrix === []) {
            return [
                'v' => $v,
                'criteria_ids' => [],
                'normalized' => [],
                'scores' => [],
                'ranking' => [],
                'threshold' => 0.0,
                'best_id' => null,
                'best_classification' => 'Tidak Diterima',
                'has_advantage' => false,
                'is_stable' => false,
            ];
        }

        // Bobot dinormalisasi supaya totalnya 1.
        $totalWeight = array_sum(array_map('floatval', $weights));
        $normalizedWeights = [];
        foreach ($weights as $criterionId => $weight) {
            $normalizedWeights[$criterionId] = $totalWeight > 0
                ? (float) $weight / $totalWeight
                : 0.0;
        }

        // Nilai mentah tiap kriteria (untuk mencari max & min)
        $rawValues = [];
        foreach ($criteriaIds as $criterionId) {
            $column = [];
            foreach ($decisionMatrix as $row) {
                if (array_key_exists($criterionId, $row)) {
                    $column[] = (float) $row[$criterionId];
                }
            }
            $rawValues[$criterionId] = $column;
        }

        // Normalisasi matriks keputusan
        $normalized = [];
        foreach ($decisionMatrix as $alternativeId => $row) {
            foreach ($criteriaIds as $criterionId) {
                $value = (float) ($row[$criterionId] ?? 0);
                $column = $rawValues[$criterionId];

                $max = $column === [] ? 0.0 : max($column);
                $min = $column === [] ? 0.0 : min($column);

                if (($criterionTypes[$criterionId] ?? 'benefit') === 'cost') {
                    // Cost: nilai kecil lebih baik -> dinormalisasi terbalik.
                    $normalized[$alternativeId][$criterionId] = ($max - $min) > 0
                        ? ($max - $value) / ($max - $min)
                        : 1.0;
                } else {
                    // Benefit: nilai besar lebih baik.
                    $normalized[$alternativeId][$criterionId] = $max > 0
                        ? $value / $max
                        : 0.0;
                }
            }
        }

        // r_j+ = nilai normalisasi terbesar tiap kriteria (dihitung dari data asli).
        $rPlus = [];
        foreach ($criteriaIds as $criterionId) {
            $column = array_map(
                fn ($alternativeId) => (float) ($normalized[$alternativeId][$criterionId] ?? 0),
                array_keys($normalized)
            );
            $rPlus[$criterionId] = $column === [] ? 0.0 : max($column);
        }

        // Ukuran utility S_i dan ukuran regresi R_i
        $sValues = [];
        $rValues = [];

        foreach ($normalized as $alternativeId => $row) {
            $s = 0.0;
            $r = 0.0;

            foreach ($criteriaIds as $criterionId) {
                $vij = $normalizedWeights[$criterionId] * (float) ($row[$criterionId] ?? 0);
                $s += $vij;

                if ($rPlus[$criterionId] > 0) {
                    $r = max($r, $vij / $rPlus[$criterionId]);
                }
            }

            $sValues[$alternativeId] = $s;
            $rValues[$alternativeId] = $r;
        }

        $sMinus = $sValues === [] ? 0.0 : min($sValues);
        $rPlusGlobal = $rValues === [] ? 0.0 : max($rValues);

        // Indeks kompromi Q_i = v * (S^- - S_i) + (1 - v) * (R_i - R^+)
        // S^- = utility terkecil (paling buruk), R^+ = regresi terbesar (paling buruk).
        $qValues = [];
        foreach ($sValues as $alternativeId => $s) {
            $qValues[$alternativeId] = $v * ($sMinus - $s) + (1 - $v) * ($rValues[$alternativeId] - $rPlusGlobal);
        }

        // Peringkat: Q terkecil = peringkat terbaik.
        // Saat Q seri, diurutkan dari utility (S) tertinggi lalu regresi (R) terkecil.
        $ranking = array_keys($qValues);
        usort($ranking, function ($a, $b) use ($qValues, $sValues, $rValues) {
            $diff = $qValues[$a] <=> $qValues[$b];

            if ($diff !== 0) {
                return $diff;
            }

            $utility = $sValues[$b] <=> $sValues[$a];

            return $utility !== 0 ? $utility : $rValues[$a] <=> $rValues[$b];
        });

        $alternativeCount = count($ranking);
        $threshold = $alternativeCount > 1 ? 1 / ($alternativeCount - 1) : 0.0;

        // Klasifikasi VIKOR: bandingkan peringkat 1 dan 2 pada ukuran S dan R.
        $best = $ranking[0] ?? null;
        $second = $ranking[1] ?? null;

        $bestClassification = 'Solusi Kompromi';
        $hasAdvantage = true;
        $isStable = true;

        if ($second !== null) {
            // Keunggulan: selisih Qalternatif ke-2 - ke-1 minimal 1/(J-1).
            $hasAdvantage = ($qValues[$second] - $qValues[$best]) >= $threshold;

            // Stabilitas: selisih Ralternatif ke-1 - ke-2 maksimal 1/(J-1).
            $isStable = ($rValues[$best] - $rValues[$second]) <= $threshold;

            $betterUtility = $sValues[$best] >= $sValues[$second];
            $betterRegret = $rValues[$best] <= $rValues[$second];

            if ($betterUtility && $betterRegret) {
                $bestClassification = 'Solusi Kompromi';
            } elseif ($betterUtility || $betterRegret) {
                $bestClassification = 'Diterima';
            } else {
                $bestClassification = 'Tidak Diterima';
            }
        }

        $scores = [];
        foreach ($ranking as $index => $alternativeId) {
            $scores[$alternativeId] = [
                'alternative_id' => $alternativeId,
                'rank' => $index + 1,
                'S' => round((float) $sValues[$alternativeId], 6),
                'R' => round((float) $rValues[$alternativeId], 6),
                'Q' => round((float) $qValues[$alternativeId], 6),
                'classification' => $alternativeId === $best
                    ? $bestClassification
                    : ($index === 1 ? 'Alternatif' : 'Tidak Diterima'),
                'is_best' => $alternativeId === $best,
                'has_advantage' => $alternativeId === $best ? $hasAdvantage : false,
                'is_stable' => $alternativeId === $best ? $isStable : false,
            ];
        }

        return [
            'v' => $v,
            'criteria_ids' => $criteriaIds,
            'normalized' => $normalized,
            'scores' => $scores,
            'ranking' => $ranking,
            'threshold' => $threshold,
            'best_id' => $best,
            'best_classification' => $bestClassification,
            'has_advantage' => $hasAdvantage,
            'is_stable' => $isStable,
        ];
    }

    /**
     * Menyusun matriks keputusan dari skor pemasok yang tersimpan.
     *
     * @param  Collection<int, Supplier>  $suppliers
     * @param  Collection<int, DssSupplierScore>  $scores
     * @param  Collection<int, DssCriterion>  $criteria
     * @return array<int, array<int, float>>
     */
    public function buildDecisionMatrix(
        Collection $suppliers,
        Collection $scores,
        Collection $criteria
    ): array {
        $criteriaIds = $criteria->pluck('id')->all();

        $lookup = [];
        foreach ($scores as $score) {
            $lookup[$score->supplier_id][$score->dss_criterion_id] = (float) $score->score;
        }

        $matrix = [];
        foreach ($suppliers as $supplier) {
            foreach ($criteriaIds as $criterionId) {
                $matrix[$supplier->id][$criterionId] = (float) ($lookup[$supplier->id][$criterionId] ?? 0);
            }
        }

        return $matrix;
    }

    /**
     * Menjalankan VIKOR langsung dari data tersimpan.
     *
     * @param  Collection<int, Supplier>  $suppliers
     * @param  Collection<int, DssCriterion>  $criteria
     * @param  array<int, float>  $weights  Bobot kriteria (hasil AHP).
     * @param  Collection<int, DssSupplierScore>|null  $scores
     * @return array{v: float, criteria_ids: array, normalized: array, scores: array, ranking: array, threshold: float, best_id: mixed, best_classification: string, has_advantage: bool, is_stable: bool}
     */
    public function analyze(
        Collection $suppliers,
        Collection $criteria,
        array $weights,
        ?Collection $scores = null,
        ?float $v = null
    ): array {
        $criteriaIds = $criteria->pluck('id')->all();

        if ($scores === null) {
            $scores = DssSupplierScore::query()
                ->whereIn('supplier_id', $suppliers->pluck('id'))
                ->whereIn('dss_criterion_id', $criteriaIds)
                ->get();
        }

        // Belum ada penilaian pemasok -> tidak ada peringkat yang bisa dihitung.
        if ($scores->isEmpty()) {
            return $this->calculate([], [], []);
        }

        $types = [];
        foreach ($criteria as $criterion) {
            $types[$criterion->id] = $criterion->type;
        }

        $matrix = $this->buildDecisionMatrix($suppliers, $scores, $criteria);

        return $this->calculate($matrix, $weights, $types, $v);
    }
}
