<?php

namespace App\Services;

use App\Models\DssCriterion;
use App\Models\DssSupplierScore;
use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Perhitungan VIKOR (VIse Kriterijum Optimizacije) dengan formulasi baku.
 *
 * Tahapan perhitungan:
 *
 * 1. Nilai terbaik `f*_j` dan terburuk `f-_j` untuk tiap kriteria j:
 *      - benefit: `f*_j` = max, `f-_j` = min
 *      - cost   : `f*_j` = min, `f-_j` = max
 * 2. Suku ternormalisasi (dalam formulasi VIKOR: "utility measure"):
 *      `d_ij = w_j * (f*_j - f_ij) / (f*_j - f-_j)`
 *    Bila `f*_j = f-_j` maka `d_ij = 0` (tidak ada pembedaan antar alternatif).
 * 3. Ukuran global dan lokal:
 *      `S_i = SUM_j d_ij`   (total penyesuaian/regret)
 *      `R_i = MAX_j d_ij`   (penyesuaian terburuk / risiko)
 *    Keduanya **makin kecil makin baik**. Alternatif yang dominan (terbaik di
 *    semua kriteria) menghasilkan `S = 0` dan `R = 0`.
 * 4. Indeks kompromi ternormalisasi:
 *      `Q_i = v * (S_i - S*) / (S- - S*) + (1 - v) * (R_i - R*) / (R- - R*)`
 *    dengan `S*` = min S, `S-` = max S, `R*` = min R, `R-` = max R.
 *    Penyebut bernilai nol maka sukunya nol. `Q` selalu berada pada rentang
 *    0..1 dan **Q terkecil = peringkat 1**.
 * 5. Peringkat: `Q` menaik; saat `Q` seri diurutkan dari `S` terkecil lalu
 *    `R` terkecil.
 * 6. Klasifikasi baku (Opricovic) dengan ambang `DQ = 1 / (J - 1)`:
 *      - C1 (keunggulan yang dapat diterima): `Q(a2) - Q(a1) >= DQ`
 *      - C2 (stabilitas yang dapat diterima): alternatif peringkat 1 juga
 *        terbaik menurut `S` atau menurut `R`
 *      - C1 & C2 terpenuhi -> solusi tunggal, yaitu alternatif 1
 *      - C1 gagal              -> kompromi semua alternatif `m` dengan
 *                                  `Q(m) - Q(1) < DQ`
 *      - C2 gagal              -> kompromi alternatif 1 dan 2
 *    Penentuan klasifikasi memakai urutan baku: C1 menentukan cakupan set
 *    kompromi, C2 menentukan apakah solusi bersifat tunggal.
 */
class VikorService
{
    /**
     * Parameter kompromi (v). Nilai 0.5 berarti bobot S dan R seimbang.
     */
    public const DEFAULT_V = 0.5;

    /**
     * Klasifikasi: alternatif 1 unggul pada Q, S, dan R -> solusi tunggal.
     */
    public const CLASSIFICATION_SINGLE = 'Solusi Tunggal';

    /**
     * Klasifikasi: C2 gagal -> kompromi antara alternatif 1 dan 2.
     */
    public const CLASSIFICATION_COMPROMISE_PAIR = 'Kompromi Alternatif 1 dan 2';

    /**
     * Klasifikasi: C1 gagal -> kompromi seluruh alternatif dalam ambang DQ.
     */
    public const CLASSIFICATION_COMPROMISE_SET = 'Kompromi Semua Alternatif';

    /**
     * Label per-alternatif: anggota set kompromi.
     */
    public const CLASSIFICATION_COMPROMISE = 'Kompromi';

    /**
     * Label per-alternatif: di luar set kompromi.
     */
    public const CLASSIFICATION_ALTERNATIVE = 'Alternatif';

    /**
     * Toleransi perbandingan floating point.
     */
    private const EPSILON = 1e-12;

    /**
     * Menghitung peringkat VIKOR beserta klasifikasi bakunya.
     *
     * @param  array<int|string, array<int|string, float>>  $decisionMatrix  [alternatif][kriteria] = nilai mentah
     * @param  array<int|string, float>  $weights  [kriteria] = bobot (tidak harus berjumlah 1)
     * @param  array<int|string, string>  $criterionTypes  [kriteria] = 'benefit' | 'cost'
     * @param  float|null  $v  Parameter kompromi 0..1 (default 0.5), nilai di luar rentang dijepit.
     * @return array{v: float, criteria_ids: array, normalized: array, scores: array, ranking: array, threshold: float, best_id: mixed, best_classification: string, compromise_set: array, has_advantage: bool, is_stable: bool}
     */
    public function calculate(
        array $decisionMatrix,
        array $weights,
        array $criterionTypes,
        ?float $v = null
    ): array {
        // v dijepit pada rentang 0..1 supaya Q selalu berada pada rentang 0..1.
        $v = max(0.0, min(1.0, $v ?? self::DEFAULT_V));

        $criteriaIds = array_keys($weights);

        if ($criteriaIds === [] || $decisionMatrix === []) {
            return $this->emptyResult($v);
        }

        // Bobot dinormalisasi supaya totalnya 1.
        $normalizedWeights = $this->normalizeWeights($weights);

        // Nilai terbaik (f*) dan terburuk (f-) tiap kriteria mengikuti tipenya.
        $bestValues = [];
        $worstValues = [];

        foreach ($criteriaIds as $criterionId) {
            $column = [];
            foreach ($decisionMatrix as $row) {
                if (array_key_exists($criterionId, $row)) {
                    $column[] = (float) $row[$criterionId];
                }
            }

            $max = $column === [] ? 0.0 : max($column);
            $min = $column === [] ? 0.0 : min($column);

            if (($criterionTypes[$criterionId] ?? 'benefit') === 'cost') {
                // Cost: nilai kecil lebih baik.
                $bestValues[$criterionId] = $min;
                $worstValues[$criterionId] = $max;
            } else {
                // Benefit: nilai besar lebih baik.
                $bestValues[$criterionId] = $max;
                $worstValues[$criterionId] = $min;
            }
        }

        // d_ij = w_j * (f*_j - f_ij) / (f*_j - f-_j)
        // S_i = SUM_j d_ij ; R_i = MAX_j d_ij
        $normalized = [];
        $sValues = [];
        $rValues = [];

        foreach ($decisionMatrix as $alternativeId => $row) {
            $sum = 0.0;
            $maxTerm = 0.0;

            foreach ($criteriaIds as $criterionId) {
                $value = (float) ($row[$criterionId] ?? 0);
                $best = $bestValues[$criterionId];
                $worst = $worstValues[$criterionId];
                $span = $best - $worst;

                // Seluruh nilai sama -> tidak ada penyesuaian untuk kriteria ini.
                $term = abs($span) > self::EPSILON
                    ? $normalizedWeights[$criterionId] * (($best - $value) / $span)
                    : 0.0;

                $normalized[$alternativeId][$criterionId] = $term;
                $sum += $term;
                $maxTerm = max($maxTerm, $term);
            }

            $sValues[$alternativeId] = $sum;
            $rValues[$alternativeId] = $maxTerm;
        }

        $sStar = min($sValues);   // S* = min S
        $sMinus = max($sValues);  // S- = max S
        $rStar = min($rValues);   // R* = min R
        $rMinus = max($rValues);  // R- = max R

        // Indeks kompromi ternormalisasi:
        // Q_i = v * (S_i - S*) / (S- - S*) + (1 - v) * (R_i - R*) / (R- - R*)
        $qValues = [];

        foreach ($sValues as $alternativeId => $s) {
            $sRatio = abs($sMinus - $sStar) > self::EPSILON
                ? ($s - $sStar) / ($sMinus - $sStar)
                : 0.0;
            $rRatio = abs($rMinus - $rStar) > self::EPSILON
                ? ($rValues[$alternativeId] - $rStar) / ($rMinus - $rStar)
                : 0.0;

            $qValues[$alternativeId] = $v * $sRatio + (1 - $v) * $rRatio;
        }

        // Peringkat: Q terkecil = peringkat terbaik.
        // Saat Q seri: S terkecil, lalu R terkecil.
        $ranking = array_keys($qValues);
        usort($ranking, function ($a, $b) use ($qValues, $sValues, $rValues) {
            $qDiff = $qValues[$a] - $qValues[$b];

            if (abs($qDiff) > self::EPSILON) {
                return $qDiff < 0 ? -1 : 1;
            }

            $sDiff = $sValues[$a] - $sValues[$b];

            if (abs($sDiff) > self::EPSILON) {
                return $sDiff < 0 ? -1 : 1;
            }

            $rDiff = $rValues[$a] - $rValues[$b];

            return abs($rDiff) > self::EPSILON ? ($rDiff < 0 ? -1 : 1) : 0;
        });

        $alternativeCount = count($ranking);
        $threshold = $alternativeCount > 1 ? 1 / ($alternativeCount - 1) : 0.0;

        $best = $ranking[0] ?? null;
        $second = $ranking[1] ?? null;

        $hasAdvantage = true;
        $isStable = true;
        $bestClassification = self::CLASSIFICATION_SINGLE;
        $compromiseSet = [];

        if ($best !== null && $second === null) {
            // Hanya ada satu alternatif -> pasti solusi tunggal.
            $compromiseSet = [$best];
        } elseif ($best !== null && $second !== null) {
            // C1 (keunggulan): selisih Q alternatif ke-2 minimal DQ.
            $hasAdvantage = ($qValues[$second] - $qValues[$best]) >= $threshold - self::EPSILON;

            // C2 (stabilitas): alternatif ke-1 juga terbaik menurut S atau R.
            $isStable = $this->isEqual($sValues[$best], $sStar) || $this->isEqual($rValues[$best], $rStar);

            if (! $hasAdvantage) {
                // C1 gagal -> kompromi semua alternatif dalam ambang DQ.
                $bestClassification = self::CLASSIFICATION_COMPROMISE_SET;

                foreach ($ranking as $alternativeId) {
                    if (($qValues[$alternativeId] - $qValues[$best]) < $threshold - self::EPSILON) {
                        $compromiseSet[] = $alternativeId;
                    }
                }
            } elseif (! $isStable) {
                // C2 gagal -> kompromi alternatif 1 dan 2.
                $bestClassification = self::CLASSIFICATION_COMPROMISE_PAIR;
                $compromiseSet = [$best, $second];
            } else {
                $bestClassification = self::CLASSIFICATION_SINGLE;
                $compromiseSet = [$best];
            }
        }

        $scores = [];
        foreach ($ranking as $index => $alternativeId) {
            $isBest = $alternativeId === $best;

            $scores[$alternativeId] = [
                'alternative_id' => $alternativeId,
                'rank' => $index + 1,
                'S' => round((float) $sValues[$alternativeId], 6),
                'R' => round((float) $rValues[$alternativeId], 6),
                'Q' => round((float) $qValues[$alternativeId], 6),
                'classification' => $isBest
                    ? $bestClassification
                    : (in_array($alternativeId, $compromiseSet, true)
                        ? self::CLASSIFICATION_COMPROMISE
                        : self::CLASSIFICATION_ALTERNATIVE),
                'is_best' => $isBest,
                'has_advantage' => $isBest ? $hasAdvantage : false,
                'is_stable' => $isBest ? $isStable : false,
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
            'compromise_set' => $compromiseSet,
            'has_advantage' => $hasAdvantage,
            'is_stable' => $isStable,
        ];
    }

    /**
     * Hasil kosong (belum ada kriteria atau alternatif).
     *
     * @return array{v: float, criteria_ids: array, normalized: array, scores: array, ranking: array, threshold: float, best_id: mixed, best_classification: string, compromise_set: array, has_advantage: bool, is_stable: bool}
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
            'best_classification' => 'Belum Ada Alternatif',
            'compromise_set' => [],
            'has_advantage' => false,
            'is_stable' => false,
        ];
    }

    /**
     * Menormalisasi bobot kriteria supaya totalnya 1.
     *
     * @param  array<int|string, float>  $weights
     * @return array<int|string, float>
     */
    private function normalizeWeights(array $weights): array
    {
        $totalWeight = array_sum(array_map('floatval', $weights));
        $normalizedWeights = [];

        foreach ($weights as $criterionId => $weight) {
            $normalizedWeights[$criterionId] = $totalWeight > 0
                ? (float) $weight / $totalWeight
                : 0.0;
        }

        return $normalizedWeights;
    }

    /**
     * Perbandingan floating point dengan toleransi.
     */
    private function isEqual(float $a, float $b): bool
    {
        return abs($a - $b) <= self::EPSILON;
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
     * @param  float|null  $v  Parameter kompromi 0..1.
     * @return array{v: float, criteria_ids: array, normalized: array, scores: array, ranking: array, threshold: float, best_id: mixed, best_classification: string, compromise_set: array, has_advantage: bool, is_stable: bool}
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
