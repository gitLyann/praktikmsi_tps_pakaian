<?php

namespace App\Services;

use App\Models\DssCriterion;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Perhitungan VIKOR (VIse Kriterijum Optimizacije) untuk peringkat alternatif.
 *
 * Rumus yang dipakai (ukuran "penyesuaian"/regret, makin kecil makin baik):
 *
 * 1. Untuk tiap kriteria j tentukan nilai terbaik f*_j dan terburuk f-_j
 *    menurut tipenya:
 *      - benefit: f*_j = max, f-_j = min
 *      - cost   : f*_j = min, f-_j = max
 * 2. Susun suku ternormalisasi (regret) per alternatif:
 *      term_ij = w_j * (f*_j - f_ij) / (f*_j - f-_j)
 *    (untuk cost: (f*_j - f_ij)/(f*_j - f-_j) = (f_ij - min)/(max - min),
 *     jadi tetap bernilai 0 untuk alternatif terbaik).
 * 3. Ukuran penyesuaian dan regresi:
 *      S_i = SUM_j term_ij        (makin kecil makin baik)
 *      R_i = MAX_j term_ij        (makin kecil makin baik)
 * 4. Indeks kompromi ternormalisasi:
 *      Q_i = v * (S_i - S*) / (S- - S*) + (1 - v) * (R_i - R*) / (R- - R*)
 *    dengan S* = min S, S- = max S, R* = min R, R- = max R.
 * 5. Peringkat: Q terkecil = peringkat terbaik.
 *    Jika Q sama, diurutkan dari S terkecil lalu R terkecil.
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
     * @param  array<int|string, array<int|string, float>>  $decisionMatrix  [alternatif][kriteria] = nilai mentah
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
            return $this->emptyResult($v);
        }

        // Bobot dinormalisasi supaya totalnya 1.
        $totalWeight = array_sum(array_map('floatval', $weights));
        $normalizedWeights = [];
        foreach ($weights as $criterionId => $weight) {
            $normalizedWeights[$criterionId] = $totalWeight > 0
                ? (float) $weight / $totalWeight
                : 0.0;
        }

        // Nilai mentah tiap kriteria (untuk mencari f* dan f-).
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

        // f*_j (terbaik) dan f-_j (terburuk) mengikuti tipe kriteria.
        $bestValue = [];
        $worstValue = [];
        foreach ($criteriaIds as $criterionId) {
            $column = $rawValues[$criterionId];
            $max = $column === [] ? 0.0 : max($column);
            $min = $column === [] ? 0.0 : min($column);

            if (($criterionTypes[$criterionId] ?? 'benefit') === 'cost') {
                // Cost: nilai kecil lebih baik.
                $bestValue[$criterionId] = $min;
                $worstValue[$criterionId] = $max;
            } else {
                // Benefit: nilai besar lebih baik.
                $bestValue[$criterionId] = $max;
                $worstValue[$criterionId] = $min;
            }
        }

        // Suku ternormalisasi: term_ij = w_j * (f*_j - f_ij) / (f*_j - f-_j).
        $terms = [];
        foreach ($decisionMatrix as $alternativeId => $row) {
            foreach ($criteriaIds as $criterionId) {
                $value = (float) ($row[$criterionId] ?? 0);
                $best = $bestValue[$criterionId];
                $worst = $worstValue[$criterionId];
                $span = $best - $worst;

                // Semua nilai sama -> tidak ada penyesuaian untuk kriteria ini.
                $normalized = abs($span) > 0
                    ? ($best - $value) / $span
                    : 0.0;

                $terms[$alternativeId][$criterionId] = $normalizedWeights[$criterionId] * $normalized;
            }
        }

        // S_i = SUM(term_ij) ; R_i = MAX(term_ij)
        $sValues = [];
        $rValues = [];

        foreach ($terms as $alternativeId => $row) {
            $sum = 0.0;
            $max = 0.0;

            foreach ($criteriaIds as $criterionId) {
                $term = (float) ($row[$criterionId] ?? 0.0);
                $sum += $term;
                $max = max($max, $term);
            }

            $sValues[$alternativeId] = $sum;
            $rValues[$alternativeId] = $max;
        }

        $sStar = $sValues === [] ? 0.0 : min($sValues);   // S* = min S
        $sMinus = $sValues === [] ? 0.0 : max($sValues);  // S- = max S
        $rStar = $rValues === [] ? 0.0 : min($rValues);   // R* = min R
        $rMinus = $rValues === [] ? 0.0 : max($rValues);  // R- = max R

        // Indeks kompromi ternormalisasi:
        // Q_i = v * (S_i - S*) / (S- - S*) + (1 - v) * (R_i - R*) / (R- - R*)
        $qValues = [];
        foreach ($sValues as $alternativeId => $s) {
            $sRatio = abs($sMinus - $sStar) > 0 ? ($s - $sStar) / ($sMinus - $sStar) : 0.0;
            $rRatio = abs($rMinus - $rStar) > 0 ? ($rValues[$alternativeId] - $rStar) / ($rMinus - $rStar) : 0.0;

            $qValues[$alternativeId] = $v * $sRatio + (1 - $v) * $rRatio;
        }

        // Peringkat: Q terkecil = peringkat terbaik.
        // Saat Q seri: S terkecil, lalu R terkecil.
        $ranking = array_keys($qValues);
        usort($ranking, function ($a, $b) use ($qValues, $sValues, $rValues) {
            $diff = $qValues[$a] <=> $qValues[$b];

            if ($diff !== 0) {
                return $diff;
            }

            $sDiff = $sValues[$a] <=> $sValues[$b];

            return $sDiff !== 0 ? $sDiff : $rValues[$a] <=> $rValues[$b];
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

            $betterUtility = $sValues[$best] <= $sValues[$second];
            $betterRegret = $rValues[$best] <= $rValues[$second];

            if ($betterUtility && $betterRegret && $hasAdvantage && $isStable) {
                $bestClassification = 'Solusi Kompromi';
            } elseif ($betterUtility || $betterRegret || $hasAdvantage) {
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
            'normalized' => $terms,
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
     * Hasil kosong (belum ada kriteria atau alternatif).
     *
     * @return array{v: float, criteria_ids: array, normalized: array, scores: array, ranking: array, threshold: float, best_id: mixed, best_classification: string, has_advantage: bool, is_stable: bool}
     */
    private function emptyResult(float $v): array
    {
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
     * @param  array<int, float>  $weights  Bobot kriteria (hasil AHP, berkunci id kriteria).
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
            return $this->emptyResult($v ?? self::DEFAULT_V);
        }

        $types = [];
        foreach ($criteria as $criterion) {
            $types[$criterion->id] = $criterion->type;
        }

        $matrix = $this->buildDecisionMatrix($suppliers, $scores, $criteria);

        return $this->calculate($matrix, $weights, $types, $v);
    }
}
