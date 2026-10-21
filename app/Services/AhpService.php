<?php

namespace App\Services;

use App\Models\DssCriterion;
use App\Models\DssCriterionComparison;
use Illuminate\Support\Collection;

/**
 * Perhitungan Analytic Hierarchy Process (AHP).
 *
 * Tahapan yang dijalankan:
 * 1. Matriks perbandingan pairwise antar kriteria.
 * 2. Normalisasi kolom (nilai dibagi jumlah kolom).
 * 3. Bobot = rata-rata baris matriks ternormalisasi.
 * 4. Lambda max, Index of Consistency (CI), Consistency Ratio (CR).
 */
class AhpService
{
    /**
     * Random Index (RI) Saaty untuk orde matrik 1-10.
     *
     * @var array<int, float>
     */
    public const RANDOM_INDEX = [
        1 => 0.00,
        2 => 0.00,
        3 => 0.58,
        4 => 0.90,
        5 => 1.12,
        6 => 1.24,
        7 => 1.32,
        8 => 1.41,
        9 => 1.45,
        10 => 1.49,
    ];

    /**
     * Ambang batas konsistensi. CR <= 0.10 dianggap konsisten.
     */
    public const CONSISTENCY_THRESHOLD = 0.10;

    /**
     * Menghitung bobot dan ukuran konsistensi dari matriks perbandingan.
     *
     * @param  array<int, array<int, float>>  $matrix  Matriks persegi, indeks mulai dari 0.
     * @return array{matrix: array, normalized: array, weights: array<int, float>, lambda_max: float, ci: float, ri: float, cr: float, is_consistent: bool, criteria_count: int}
     */
    public function calculate(array $matrix): array
    {
        $n = count($matrix);

        foreach ($matrix as $row) {
            if (count($row) !== $n) {
                throw new \InvalidArgumentException('Matriks perbandingan harus berbentuk persegi.');
            }
        }

        if ($n === 0) {
            return $this->emptyResult();
        }

        if ($n === 1) {
            return [
                'matrix' => $matrix,
                'normalized' => [[1.0]],
                'weights' => [1.0],
                'lambda_max' => 1.0,
                'ci' => 0.0,
                'ri' => 0.0,
                'cr' => 0.0,
                'is_consistent' => true,
                'criteria_count' => 1,
            ];
        }

        // Jumlah tiap kolom
        $columnSums = array_fill(0, $n, 0.0);
        foreach ($matrix as $row) {
            foreach ($row as $j => $value) {
                $columnSums[$j] += (float) $value;
            }
        }

        // Normalisasi kolom
        $normalized = [];
        foreach ($matrix as $i => $row) {
            foreach ($row as $j => $value) {
                $normalized[$i][$j] = $columnSums[$j] > 0 ? (float) $value / $columnSums[$j] : 0.0;
            }
        }

        // Bobot = rata-rata baris matriks ternormalisasi
        $weights = [];
        foreach ($normalized as $i => $row) {
            $weights[$i] = array_sum($row) / $n;
        }

        // Lambda max = (1/n) * SUM((A * w)_i / w_i) memakai matriks ASLI
        $lambdaMax = 0.0;
        foreach ($matrix as $i => $row) {
            $weightedSum = 0.0;
            foreach ($row as $j => $value) {
                $weightedSum += (float) $value * $weights[$j];
            }

            if ($weights[$i] > 0) {
                $lambdaMax += $weightedSum / $weights[$i];
            }
        }
        $lambdaMax /= $n;

        $ri = self::RANDOM_INDEX[$n] ?? 0.0;
        // CI negatif hanya muncul karena pembulatan, dibatasi agar tidak rattling.
        $ci = max(0.0, ($lambdaMax - $n) / ($n - 1));
        $cr = $ri > 0 ? $ci / $ri : 0.0;

        return [
            'matrix' => $matrix,
            'normalized' => $normalized,
            'weights' => $weights,
            'lambda_max' => $lambdaMax,
            'ci' => $ci,
            'ri' => $ri,
            'cr' => $cr,
            'is_consistent' => $cr <= self::CONSISTENCY_THRESHOLD,
            'criteria_count' => $n,
        ];
    }

    /**
     * Membangun matriks perbandingan dari daftar kriteria dan data perbandingan.
     *
     * Kriteria yang belum punya pasangan perbandingan dianggap bernilai 1
     * (sama penting) supaya matriks tetap utuh.
     *
     * @param  Collection<int, DssCriterion>  $criteria
     * @param  Collection<int, DssCriterionComparison>  $comparisons
     * @return array<int, array<int, float>>
     */
    public function buildMatrix(Collection $criteria, Collection $comparisons): array
    {
        $ids = $criteria->pluck('id')->values()->all();

        $values = [];
        foreach ($comparisons as $comparison) {
            $values[$comparison->criterion_a_id][$comparison->criterion_b_id] = (float) $comparison->value;
        }

        $matrix = [];
        foreach ($ids as $i => $a) {
            foreach ($ids as $j => $b) {
                $matrix[$i][$j] = $i === $j ? 1.0 : ($values[$a][$b] ?? 1.0);
            }
        }

        return $matrix;
    }

    /**
     * Menjalankan analisis AHP untuk sekumpulan kriteria.
     *
     * @param  Collection<int, DssCriterion>  $criteria
     * @param  Collection<int, DssCriterionComparison>|null  $comparisons
     * @return array{matrix: array, normalized: array, weights: array<int, float>, criteria_weights: array<int, float>, lambda_max: float, ci: float, ri: float, cr: float, is_consistent: bool, criteria_count: int}
     */
    public function analyze(Collection $criteria, ?Collection $comparisons = null): array
    {
        $criteria = $criteria->sortBy('sort_order')->values();

        if ($comparisons === null) {
            $comparisons = DssCriterionComparison::query()
                ->whereIn('criterion_a_id', $criteria->pluck('id'))
                ->whereIn('criterion_b_id', $criteria->pluck('id'))
                ->get();
        }

        $matrix = $this->buildMatrix($criteria, $comparisons);
        $result = $this->calculate($matrix);

        // Bobot dinormalisasi lagi agar totalnya tepat 1 (berguna saat dipakai VIKOR).
        $criteriaWeights = [];
        $ids = $criteria->pluck('id')->values()->all();
        foreach ($result['weights'] as $index => $weight) {
            $criteriaWeights[$ids[$index]] = round((float) $weight, 6);
        }

        $result['criteria_weights'] = $criteriaWeights;

        return $result;
    }

    /**
     * Hasil kosong untuk kasus tanpa kriteria.
     *
     * @return array{matrix: array, normalized: array, weights: array, lambda_max: float, ci: float, ri: float, cr: float, is_consistent: bool, criteria_count: int}
     */
    private function emptyResult(): array
    {
        return [
            'matrix' => [],
            'normalized' => [],
            'weights' => [],
            'lambda_max' => 0.0,
            'ci' => 0.0,
            'ri' => 0.0,
            'cr' => 0.0,
            'is_consistent' => true,
            'criteria_count' => 0,
        ];
    }
}
