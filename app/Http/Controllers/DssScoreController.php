<?php

namespace App\Http\Controllers;

use App\Services\DssAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman penilaian DSS untuk staff (kasir).
 *
 * Staff mengisi skor penilaian pemasok terhadap tiap kriteria; hasil penilaian
 * dipakai admin untuk menjalankan analisis VIKOR.
 *
 * Halaman ini punya dua mode: HTML (halaman penilaian) dan JSON (API untuk
 * integrasi/mobile). Mode dipilih lewat header `Accept`.
 */
class DssScoreController extends Controller
{
    public function __construct(
        private readonly DssAnalysisService $analysis
    ) {}

    /**
     * Data penilaian: kriteria (beserta bobot AHP), pemasok, dan skor yang sudah tersimpan.
     */
    public function index(Request $request): JsonResponse|View
    {
        $run = $this->analysis->activeScoringRun();
        $criteria = $this->analysis->criteriaWithWeights();
        $suppliers = $this->analysis->suppliersWithScores();

        if ($request->expectsJson()) {
            return response()->json([
                'criteria' => $criteria,
                'suppliers' => $suppliers,
                'run' => $run === null ? null : [
                    'id' => $run->id,
                    'name' => $run->name,
                    'status' => $run->status,
                ],
            ]);
        }

        return view('staff.dss', [
            'title' => 'Penilaian Pemasok - DSS',
            'sidebar' => true,
            'activeRoute' => 'dss',
            'dashboardRoute' => 'staff.dashboard',
            'criteria' => $criteria,
            'suppliers' => $suppliers,
            'run' => $run,
        ]);
    }

    /**
     * Menyimpan skor penilaian pemasok.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'scores.*.criterion_id' => ['required', 'integer', 'exists:dss_criteria,id'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Skor yang dikosongkan pada form tidak ikut disimpan.
        $scores = array_values(array_filter(
            $validated['scores'],
            fn (array $score): bool => $score['score'] !== null
        ));

        $result = $this->analysis->saveScores($scores, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'run' => [
                    'id' => $result['run']->id,
                    'name' => $result['run']->name,
                    'status' => $result['run']->status,
                ],
                'saved' => $result['saved'],
            ], 201);
        }

        return redirect()->route('staff.dss')->with(
            'success',
            'Penilaian tersimpan: '.$result['saved'].' skor pada run #'.$result['run']->id.'.'
        );
    }
}
