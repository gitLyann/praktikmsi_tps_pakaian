<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\EisFilterRequest;
use App\Models\Employee;
use App\Models\StaffPerformanceSnapshot;
use App\Services\EisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EisController extends Controller
{
    public function __construct(private readonly EisService $eis) {}

    public function index(EisFilterRequest $request): View
    {
        $employee = $this->employee($request);
        $period = $this->eis->normalizePeriod($request->period());

        if (StaffPerformanceSnapshot::query()
            ->where('employee_id', $employee->id)
            ->where('period', $period)
            ->doesntExist()) {
            $this->eis->buildSnapshots($period);
        }

        return view('staff.eis-kinerja-saya', array_merge($this->eis->myPerformance($employee, $period), [
            'period' => $period,
            'periods' => $this->periodOptions(),
            'activityMix' => $this->eis->myActivityMix($employee, $period),
        ]));
    }

    public function data(EisFilterRequest $request, string $name): JsonResponse
    {
        $employee = $this->employee($request);
        $period = $this->eis->normalizePeriod($request->period());
        $detail = $this->eis->myPerformance($employee, $period);

        $data = match ($name) {
            'summary' => [
                'period' => $period,
                'snapshot' => $detail['snapshot'],
            ],
            'trend' => $detail['trend'],
            'activity-mix' => $this->eis->myActivityMix($employee, $period),
            'history' => [
                'transactions' => $detail['transactions'],
                'restocks' => $detail['restocks'],
                'activity_logs' => $detail['activity_logs'],
            ],
            default => abort(404),
        };

        return response()->json($data);
    }

    private function employee(Request $request): Employee
    {
        $employee = $request->user()?->employee;
        abort_unless($employee !== null, 403, 'Profil staf belum terhubung.');

        return $employee;
    }

    private function periodOptions(): Collection
    {
        return collect(range(0, (int) config('eis.filter.max_months', 24) - 1))
            ->map(fn (int $months) => now()->subMonths($months)->format('Y-m'));
    }
}
