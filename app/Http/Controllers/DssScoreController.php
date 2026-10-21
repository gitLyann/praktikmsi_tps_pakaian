<?php

namespace App\Http\Controllers;

use App\Models\RestockRequest;
use App\Services\DssAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman DSS untuk staff (kasir).
 *
 * Staff mengisi skor penilaian pemasok, melihat hasil ranking VIKOR, dan
 * mengajukan restock untuk produk yang sudah berada di bawah reorder point.
 */
class DssScoreController extends Controller
{
    public function __construct(
        private readonly DssAnalysisService $analysis
    ) {}

    /**
     * Data penilaian: kriteria (beserta bobot AHP), pemasok, dan skor yang sudah tersimpan.
     *
     * Halaman ini juga menampilkan rekomendasi restock (read-only) dan ranking
     * VIKOR supaya staff punya konteks sebelum menilai pemasok.
     */
    public function index(Request $request): JsonResponse|View
    {
        $run = $this->analysis->activeScoringRun();
        $criteria = $this->analysis->criteriaWithWeights();
        $suppliers = $this->analysis->suppliersWithScores();

        $analysis = $this->analysis->analyze();
        $restock = $analysis['restock'];
        $vikor = $analysis['vikor'];

        $restockProductIds = collect($restock['items'])->pluck('product_id')->all();

        $restockRequests = RestockRequest::query()
            ->with(['product', 'supplier'])
            ->whereIn('product_id', $restockProductIds)
            ->latest()
            ->limit(10)
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'criteria' => $criteria,
                'suppliers' => $suppliers,
                'run' => $run === null ? null : [
                    'id' => $run->id,
                    'name' => $run->name,
                    'status' => $run->status,
                ],
                'restock' => $restock,
                'vikor' => $vikor,
                'restock_requests' => $restockRequests,
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
            'restock' => $restock,
            'vikor' => $vikor,
            'restockRequests' => $restockRequests,
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

    /**
     * Mengajukan restock untuk satu produk berdasarkan rekomendasi DSS.
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
                ->route('staff.dss')->withFragment('dss-restock')
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
            ->route('staff.dss')->withFragment('dss-restock')
            ->with('success', 'Pengajuan restock #'.$restockRequest->id.' terkirim ke Manager.');
    }
}
