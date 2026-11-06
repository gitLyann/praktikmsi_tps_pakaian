<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EsFilterRequest;
use App\Models\EsKpiTarget;
use App\Services\EsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EsController extends Controller
{
    public function __construct(private readonly EsService $es) {}

    public function index(): View
    {
        return view('admin.es', [
            'title' => 'Dashboard Eksekutif (ES)',
            'sidebar' => true,
            'activeRoute' => 'es',
            'dashboardRoute' => 'admin.dashboard',
        ]);
    }

    public function summary(EsFilterRequest $request): JsonResponse
    {
        return response()->json($this->es->kpiSummary($request->resolvedRange()));
    }

    public function chart(EsFilterRequest $request, string $name): JsonResponse
    {
        $range = $request->resolvedRange();
        $data = match ($name) {
            'revenue-trend' => $this->es->revenueTrend($range),
            'sales-by-category' => $this->es->salesByCategory($range),
            'top-products' => $this->es->topProducts($range),
            'payment-methods' => $this->es->paymentMethods($range),
            'order-status' => $this->es->orderStatus($range),
            'stock-vs-reorder' => $this->es->stockVsReorder()['chart'],
            'supplier-ranking' => $this->es->supplierRanking(),
            default => abort(404),
        };

        return response()->json($data);
    }

    public function alerts(EsFilterRequest $request): JsonResponse
    {
        return response()->json($this->es->alerts($request->resolvedRange()));
    }

    public function integration(EsFilterRequest $request): JsonResponse
    {
        return response()->json($this->es->integration($request->resolvedRange()));
    }

    public function target(Request $request): JsonResponse
    {
        $period = $request->input('period', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $period)) {
            $period = now()->format('Y-m');
        }
        return response()->json($this->es->revenueTarget($period));
    }

    public function storeTarget(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'revenue_target' => ['required', 'numeric', 'gt:0'],
        ]);
        EsKpiTarget::query()->updateOrCreate(
            ['period' => $validated['period']],
            ['revenue_target' => $validated['revenue_target'], 'created_by' => $request->user()->id]
        );
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $this->es->revenueTarget($validated['period'])]);
        }
        return redirect()->route('admin.es')->with('success', 'Target omzet periode '.$validated['period'].' disimpan.');
    }
}
