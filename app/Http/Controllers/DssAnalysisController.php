<?php

namespace App\Http\Controllers;

use App\Models\DssRun;
use App\Services\DssAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman analisis DSS untuk admin (manager).
 *
 * Admin membaca hasil AHP (bobot & konsistensi), ranking VIKOR, dan
 * rekomendasi restock, lalu menyimpan analisis sebagai riwayat run.
 */
class DssAnalysisController extends Controller
{
    public function __construct(
        private readonly DssAnalysisService $analysis
    ) {}

    /**
     * Ringkasan hasil analisis DSS (AHP, VIKOR, rekomendasi restock).
     */
    public function summary(Request $request): JsonResponse|View
    {
        $analysis = $this->analysis->analyze();

        if ($request->expectsJson()) {
            return response()->json($analysis);
        }

        return view('admin.dss', [
            'title' => 'Analisis DSS - AHP & VIKOR',
            'sidebar' => true,
            'activeRoute' => 'dss',
            'dashboardRoute' => 'admin.dashboard',
            'analysis' => $analysis,
            'criteria' => $this->analysis->criteriaWithWeights(),
            'suppliers' => $this->analysis->suppliersWithScores(),
            'runs' => DssRun::query()->latest()->limit(10)->get(),
        ]);
    }

    /**
     * Daftar pemasok beserta skor penilaian per kriteria.
     */
    public function suppliers(): JsonResponse
    {
        return response()->json([
            'criteria' => $this->analysis->criteriaWithWeights(),
            'suppliers' => $this->analysis->suppliersWithScores(),
        ]);
    }

    /**
     * Menjalankan analisis DSS dan menyimpannya sebagai riwayat run.
     */
    public function run(Request $request): JsonResponse|RedirectResponse
    {
        $analysis = $this->analysis->analyze();
        $run = $this->analysis->storeRun($request->user(), $analysis);

        if ($request->expectsJson()) {
            return response()->json([
                'run' => [
                    'id' => $run->id,
                    'name' => $run->name,
                    'type' => $run->type,
                    'status' => $run->status,
                    'created_at' => $run->created_at?->toDateTimeString(),
                ],
                'analysis' => $analysis,
            ], 201);
        }

        return redirect()->route('admin.dss')->with(
            'success',
            'Analisis DSS dijalankan dan disimpan sebagai run #'.$run->id.'.'
        );
    }
}
