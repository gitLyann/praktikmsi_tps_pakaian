<?php

namespace App\Http\Controllers;

use App\Models\DssRun;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\Supplier;
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

        $latestRun = $this->analysis->latestCompletedRun();

        return view('admin.dss', [
            'title' => 'Analisis DSS - AHP & VIKOR',
            'sidebar' => true,
            'activeRoute' => 'dss',
            'dashboardRoute' => 'admin.dashboard',
            'analysis' => $analysis,
            'criteria' => $this->analysis->criteriaWithWeights(),
            'suppliers' => $this->analysis->suppliersWithScores(),
            'runs' => DssRun::query()->latest()->limit(10)->get(),
            'matrix' => $this->analysis->comparisonMatrix(),
            'latestRun' => $latestRun,
            'activeSupplierCount' => $this->analysis->activeSupplierCount(),
            'restockRequests' => RestockRequest::query()
                ->with(['product', 'supplier'])
                ->whereNotNull('dss_run_id')
                ->latest()
                ->limit(10)
                ->get(),
            'supplierList' => Supplier::query()
                ->with('productSuppliers.product')
                ->orderBy('name')
                ->get(),
            'products' => Product::query()->orderBy('name')->get(),
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
     * Menyimpan matriks perbandingan kriteria (AHP) dari form admin.
     */
    public function saveWeights(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'values' => ['required', 'array'],
            'values.*' => ['nullable', 'numeric', 'min:1', 'max:9'],
        ]);

        $comparisons = [];

        foreach ($validated['values'] as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $parts = explode('-', (string) $key);

            if (count($parts) !== 2) {
                continue;
            }

            $comparisons[] = [
                'criterion_a_id' => (int) $parts[0],
                'criterion_b_id' => (int) $parts[1],
                'value' => (float) $value,
            ];
        }

        $result = $this->analysis->saveComparisons($comparisons);

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => $result['saved'],
                'criteria_weights' => $result['criteria_weights'],
                'lambda_max' => $result['lambda_max'],
                'ci' => $result['ci'],
                'ri' => $result['ri'],
                'cr' => $result['cr'],
                'is_consistent' => $result['is_consistent'],
            ]);
        }

        return redirect()
            ->route('admin.dss')->withFragment('dss-bobot-ahp')
            ->with('success', 'Bobot AHP disimpan ('.$result['saved'].' perbandingan, CR = '
                .number_format($result['cr'], 4).').');
    }

    /**
     * Membuat pengajuan restock dari rekomendasi DSS.
     */
    public function restockRequest(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'jumlah_restock' => ['nullable', 'integer', 'min:1'],
        ]);

        $restockRequest = $this->analysis->restockRequestFor(
            (int) $validated['product_id'],
            $request->user(),
            isset($validated['jumlah_restock']) ? (int) $validated['jumlah_restock'] : null
        );

        if ($restockRequest === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Produk tidak memiliki rekomendasi restock.',
                ], 422);
            }

            return redirect()
                ->route('admin.dss')->withFragment('dss-restock')
                ->with('error', 'Produk tersebut tidak memiliki rekomendasi restock.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'restock_request' => [
                    'id' => $restockRequest->id,
                    'product_id' => $restockRequest->product_id,
                    'jumlah_restock' => $restockRequest->jumlah_restock,
                    'status' => $restockRequest->status,
                    'supplier_id' => $restockRequest->supplier_id,
                    'dss_run_id' => $restockRequest->dss_run_id,
                ],
            ], 201);
        }

        return redirect()
            ->route('admin.dss')->withFragment('dss-restock')
            ->with('success', 'Pengajuan restock #'.$restockRequest->id.' dibuat dari rekomendasi DSS.');
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
