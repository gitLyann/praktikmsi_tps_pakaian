<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EisFilterRequest;
use App\Http\Requests\EisTargetRequest;
use App\Http\Requests\EisWeightsRequest;
use App\Models\EisException;
use App\Models\StaffPerformanceSnapshot;
use App\Models\StaffTarget;
use App\Services\EisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EisController extends Controller
{
    public function __construct(private readonly EisService $eis) {}

    public function index(EisFilterRequest $request): View
    {
        $period = $this->eis->normalizePeriod($request->period());
        $this->ensureSnapshots($period);

        return view('admin.eis', [
            'period' => $period,
            'periods' => $this->periodOptions(),
            'overview' => $this->eis->staffOverview($period),
            'ranking' => $this->eis->staffRanking($period),
            'trend' => $this->eis->trendBySales($period),
            'activityMix' => $this->eis->activityMix($period),
            'exceptions' => $this->eis->exceptions($period),
            'criteria' => $this->eis->criteriaWithAhp(),
        ]);
    }

    public function detail(EisFilterRequest $request, int $employee): View
    {
        $employeeModel = $this->eis->resolveEmployee($employee);
        abort_unless($employeeModel !== null, 404);

        $period = $this->eis->normalizePeriod($request->period());
        $this->ensureSnapshots($period);

        return view('admin.eis-detail', array_merge($this->eis->staffDetail($employeeModel, $period), [
            'periods' => $this->periodOptions(),
        ]));
    }

    public function data(EisFilterRequest $request, string $name): JsonResponse
    {
        $period = $this->eis->normalizePeriod($request->period());

        $data = match ($name) {
            'summary' => $this->eis->staffOverview($period),
            'ranking' => ['rows' => $this->eis->staffRanking($period)],
            'trend' => $this->eis->trendBySales($period),
            'activity-mix' => $this->eis->activityMix($period),
            'exceptions' => ['rows' => $this->eis->exceptions($period)],
            'criteria' => $this->eis->criteriaWithAhp(),
            default => abort(404),
        };

        return response()->json($data);
    }

    public function storeTarget(EisTargetRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        abort_unless($this->eis->resolveEmployee((int) $validated['employee_id']) !== null, 404);

        StaffTarget::query()->updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'period' => $validated['period']],
            [
                'sales_target' => $validated['sales_target'],
                'transaction_target' => $validated['transaction_target'],
            ]
        );

        $this->eis->buildSnapshots((string) $validated['period']);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('success', 'Target staf berhasil disimpan.');
    }

    public function saveWeights(EisWeightsRequest $request): JsonResponse|RedirectResponse
    {
        $matrix = [];
        foreach ($request->validated('matrix') as $row) {
            $matrix[] = array_map('floatval', $row);
        }

        $result = $this->eis->saveWeights($matrix);
        $this->eis->buildSnapshots(now()->format('Y-m'));

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'cr' => $result['cr']]);
        }

        return redirect()->back()->with('success', 'Bobot kriteria EIS diperbarui.');
    }

    public function resolveException(EisException $exception): JsonResponse|RedirectResponse
    {
        $this->eis->resolveException($exception);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('success', 'Exception EIS ditandai selesai.');
    }

    private function ensureSnapshots(string $period): void
    {
        if (StaffPerformanceSnapshot::query()->where('period', $period)->doesntExist()) {
            $this->eis->buildSnapshots($period);
        }
    }

    private function periodOptions(): Collection
    {
        return collect(range(0, (int) config('eis.filter.max_months', 24) - 1))
            ->map(fn (int $months) => now()->subMonths($months)->format('Y-m'));
    }
}
