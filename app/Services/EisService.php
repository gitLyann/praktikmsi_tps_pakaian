<?php

namespace App\Services;

use App\Models\EisCriterion;
use App\Models\EisException;
use App\Models\Employee;
use App\Models\RestockRequest;
use App\Models\StaffActivityLog;
use App\Models\StaffPerformanceSnapshot;
use App\Models\StaffTarget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EisService
{
    public function __construct(
        private readonly AhpService $ahp,
        private readonly VikorService $vikor,
        private readonly EisActivityLogger $logger,
    ) {}

    public function normalizePeriod(?string $period): string
    {
        if ($period === null || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) !== 1) {
            return now()->format('Y-m');
        }

        $parsed = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $oldest = now()->startOfMonth()->subMonths((int) config('eis.filter.max_months', 24));

        return $parsed->lt($oldest) || $parsed->gt(now()->startOfMonth())
            ? now()->format('Y-m')
            : $parsed->format('Y-m');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodBounds(string $period): array
    {
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        return [$start, (clone $start)->endOfMonth()];
    }

    public function activeEmployees(): Collection
    {
        return Employee::query()
            ->whereHas('user', fn ($query) => $query->where('role', 'staff'))
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    public function resolveEmployee(int $employeeId): ?Employee
    {
        return Employee::query()
            ->whereKey($employeeId)
            ->whereHas('user', fn ($query) => $query->where('role', 'staff'))
            ->with('user')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function staffOverview(string $period): array
    {
        $period = $this->normalizePeriod($period);

        $snapshots = StaffPerformanceSnapshot::query()
            ->where('period', $period)
            ->with('employee.user')
            ->get();

        $best = $snapshots->firstWhere('rank', 1);

        return [
            'period' => $period,
            'active_staff' => $this->activeEmployees()->count(),
            'avg_target_achievement' => round((float) $snapshots->avg('target_achievement'), 2),
            'best_staff' => $best === null ? null : [
                'employee_id' => (int) $best->employee_id,
                'name' => $best->employee?->name,
                'score' => $best->performance_score,
            ],
            'open_exceptions' => EisException::query()
                ->where('period', $period)
                ->whereNull('resolved_at')
                ->count(),
        ];
    }

    public function staffRanking(string $period): Collection
    {
        return StaffPerformanceSnapshot::query()
            ->where('period', $this->normalizePeriod($period))
            ->with('employee.user')
            ->orderBy('rank')
            ->orderByDesc('target_achievement')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function staffDetail(Employee $employee, string $period): array
    {
        $period = $this->normalizePeriod($period);
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        $snapshot = StaffPerformanceSnapshot::query()
            ->where('employee_id', $employee->id)
            ->where('period', $period)
            ->first();

        $transactions = $this->transactionRows($employee, $period)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        $restocks = RestockRequest::query()
            ->where('employee_id', $employee->id)
            ->where('created_at', '>=', $start)
            ->with('product')
            ->latest()
            ->limit(10)
            ->get();

        $logs = StaffActivityLog::query()
            ->where('employee_id', $employee->id)
            ->where('occurred_at', '>=', $start)
            ->latest('occurred_at')
            ->limit(10)
            ->get();

        $exceptions = EisException::query()
            ->where('employee_id', $employee->id)
            ->where('period', $period)
            ->latest()
            ->get();

        return [
            'employee' => $employee->load('user'),
            'period' => $period,
            'target' => StaffTarget::query()
                ->where('employee_id', $employee->id)
                ->where('period', $period)
                ->first(),
            'snapshot' => $snapshot,
            'transactions' => $transactions,
            'restocks' => $restocks,
            'activity_logs' => $logs,
            'exceptions' => $exceptions,
            'trend' => $this->trendForEmployees(collect([$employee]), $period),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function myPerformance(Employee $employee, string $period): array
    {
        $detail = $this->staffDetail($employee, $period);
        unset($detail['employee']);

        return $detail;
    }

    /**
     * @return array<string, mixed>
     */
    public function trendBySales(string $period): array
    {
        return $this->trendForEmployees($this->activeEmployees(), $period);
    }

    /**
     * @return array<string, mixed>
     */
    public function activityMix(string $period): array
    {
        [$start, $end] = $this->periodBounds($this->normalizePeriod($period));
        $employeeIds = $this->activeEmployees()->pluck('id');

        $rows = StaffActivityLog::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('activity_type, COUNT(*) as total')
            ->groupBy('activity_type')
            ->pluck('total', 'activity_type');

        $labels = [
            'transaction_processed' => 'Transaksi Diproses',
            'restock_requested' => 'Restock Diajukan',
            'restock_decided' => 'Restock Diputuskan',
            'supplier_evaluated' => 'Supplier Dinilai',
        ];

        return [
            'labels' => array_values($labels),
            'data' => array_values(array_map(fn (string $key) => (int) ($rows[$key] ?? 0), array_keys($labels))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function myActivityMix(Employee $employee, string $period): array
    {
        [$start, $end] = $this->periodBounds($this->normalizePeriod($period));
        $rows = StaffActivityLog::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('activity_type, COUNT(*) as total')
            ->groupBy('activity_type')
            ->pluck('total', 'activity_type');

        $labels = [
            'transaction_processed' => 'Transaksi Diproses',
            'restock_requested' => 'Restock Diajukan',
            'restock_decided' => 'Restock Diputuskan',
            'supplier_evaluated' => 'Supplier Dinilai',
        ];

        return [
            'labels' => array_values($labels),
            'data' => array_values(array_map(fn (string $key) => (int) ($rows[$key] ?? 0), array_keys($labels))),
        ];
    }

    public function exceptions(string $period): Collection
    {
        return EisException::query()
            ->where('period', $this->normalizePeriod($period))
            ->with('employee.user')
            ->orderByDesc('severity')
            ->latest()
            ->get();
    }

    public function resolveException(EisException $exception): EisException
    {
        $exception->forceFill(['resolved_at' => now()])->save();

        return $exception;
    }

    /**
     * @return array<string, mixed>
     */
    public function criteriaWithAhp(): array
    {
        $criteria = EisCriterion::query()->orderBy('id')->get();
        $matrix = $this->ahpMatrixFromCriteria($criteria);

        return [
            'criteria' => $criteria,
            'matrix' => $matrix,
            'ahp' => $criteria->isEmpty()
                ? $this->ahp->calculate([])
                : $this->ahp->calculate($matrix),
        ];
    }

    /**
     * @param  array<int, array<int, float|int>>  $matrix
     * @return array<string, mixed>
     */
    public function saveWeights(array $matrix): array
    {
        $criteria = EisCriterion::query()->orderBy('id')->get();

        try {
            $result = $this->ahp->calculate($matrix);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['matrix' => $exception->getMessage()]);
        }

        if (count($matrix) !== $criteria->count() || $result['cr'] >= AhpService::CONSISTENCY_THRESHOLD) {
            throw ValidationException::withMessages([
                'matrix' => 'Matriks tidak konsisten. CR harus kurang dari 0,1.',
            ]);
        }

        foreach ($criteria as $index => $criterion) {
            $criterion->forceFill(['weight' => round((float) $result['weights'][$index], 4)])->save();
        }

        return $result;
    }

    public function buildSnapshots(string $period): Collection
    {
        $period = $this->normalizePeriod($period);
        $employees = $this->activeEmployees();
        $criteria = EisCriterion::query()->orderBy('id')->get();
        $metricsById = [];
        $scoresById = [];

        foreach ($employees as $employee) {
            $metricsById[$employee->id] = $this->metricsFor($employee, $period);
        }

        if ($criteria->isNotEmpty() && $employees->isNotEmpty()) {
            $decisionMatrix = [];
            $weights = [];
            $types = [];

            foreach ($criteria as $criterion) {
                $key = (int) $criterion->id;
                $default = config('eis.criteria.'.$criterion->code, []);
                $metric = $default['metric'] ?? 'total_sales';

                foreach ($employees as $employee) {
                    $decisionMatrix[$employee->id][$key] = (float) ($metricsById[$employee->id][$metric] ?? 0);
                }

                $weights[$key] = (float) $criterion->weight;
                $types[$key] = $criterion->type;
            }

            $vikor = $this->vikor->calculate(
                $decisionMatrix,
                $weights,
                $types,
                (float) config('eis.vikor.v', 0.5)
            );

            foreach ($vikor['scores'] as $alternativeId => $row) {
                $scoresById[$alternativeId] = $row;
            }
        }

        foreach ($employees as $employee) {
            $this->writeSnapshot($employee, $period, $metricsById[$employee->id], $scoresById[$employee->id] ?? null);
        }

        return $this->staffRanking($period);
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array<string, mixed>|null  $scoreRow
     */
    private function writeSnapshot(Employee $employee, string $period, array $metrics, ?array $scoreRow): void
    {
        $target = StaffTarget::query()
            ->where('employee_id', $employee->id)
            ->where('period', $period)
            ->first();

        $salesTarget = (float) ($target?->sales_target ?? 0);
        $transactionTarget = (int) ($target?->transaction_target ?? 0);

        $achievement = $salesTarget > 0
            ? ($metrics['total_sales'] / $salesTarget) * 100
            : ($transactionTarget > 0 ? ($metrics['transactions_count'] / $transactionTarget) * 100 : 0);

        $metrics['target_achievement'] = round($achievement, 2);
        $metrics['status'] = $this->statusFor($metrics);
        $this->syncExceptions($employee, $period, $metrics);

        StaffPerformanceSnapshot::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'period' => $period],
            [
                'transactions_count' => $metrics['transactions_count'],
                'total_sales' => $metrics['total_sales'],
                'avg_order_value' => $metrics['avg_order_value'],
                'restock_submitted' => $metrics['restock_submitted'],
                'restock_approved' => $metrics['restock_approved'],
                'evaluations_count' => $metrics['evaluations_count'],
                'avg_response_hours' => $metrics['avg_response_hours'],
                'target_achievement' => $metrics['target_achievement'],
                'performance_score' => $scoreRow === null ? null : (float) $scoreRow['Q'],
                'rank' => $scoreRow === null ? null : (int) $scoreRow['rank'],
                'status' => $metrics['status'],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function metricsFor(Employee $employee, string $period): array
    {
        [$start, $end] = $this->periodBounds($period);

        $transactions = $this->transactionRows($employee, $period);
        $totalSales = (float) $transactions->sum('total');
        $transactionCount = $transactions->count();

        $restocks = RestockRequest::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $approved = $restocks->where('status', 'approved')->count();
        $rejected = $restocks->where('status', 'rejected')->count();
        $submitted = $restocks->count();

        $responseHours = $restocks
            ->whereIn('status', ['approved', 'rejected'])
            ->map(fn (RestockRequest $row) => max(0, $row->created_at->diffInHours($row->updated_at ?? $row->created_at)))
            ->avg() ?? 0;

        $evaluations = StaffActivityLog::query()
            ->where('employee_id', $employee->id)
            ->where('activity_type', 'supplier_evaluated')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        $hasRecentActivity = StaffActivityLog::query()
            ->where('employee_id', $employee->id)
            ->where('occurred_at', '>=', now()->subDays((int) config('eis.exception.inactive_days', 7)))
            ->exists();

        return [
            'transactions_count' => $transactionCount,
            'total_sales' => round($totalSales, 2),
            'avg_order_value' => $transactionCount > 0 ? round($totalSales / $transactionCount, 2) : 0,
            'restock_submitted' => $submitted,
            'restock_approved' => $approved,
            'approval_rate' => $submitted > 0 ? round($approved / $submitted * 100, 2) : 0,
            'rejection_rate' => $submitted > 0 ? round($rejected / $submitted * 100, 2) : 0,
            'evaluations_count' => (int) $evaluations,
            'avg_response_hours' => round((float) $responseHours, 2),
            'has_recent_activity' => $hasRecentActivity,
        ];
    }

    private function transactionRows(Employee $employee, string $period): Collection
    {
        [$start, $end] = $this->periodBounds($period);

        return Transaction::query()
            ->where('status', 'completed')
            ->when(
                DB::connection()->getDriverName() === 'mysql',
                fn ($query) => $query->where('employee_id', $employee->user_id),
                fn ($query) => $query->where('employee_id', $employee->id)
            )
            ->whereBetween('date', [$start, $end])
            ->get();
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function statusFor(array $metrics): string
    {
        $red = (float) config('eis.target.red_below', 70);
        $yellow = (float) config('eis.target.yellow_below', 90);
        $achievement = (float) $metrics['target_achievement'];

        if (
            $achievement < $red
            || (float) $metrics['rejection_rate'] > (float) config('eis.exception.rejected_rate_above', 50)
            || (float) $metrics['avg_response_hours'] > (float) config('eis.exception.response_hours_above', 24)
            || ! $metrics['has_recent_activity']
        ) {
            return 'red';
        }

        return $achievement < $yellow ? 'yellow' : 'green';
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function syncExceptions(Employee $employee, string $period, array $metrics): void
    {
        $red = (float) config('eis.target.red_below', 70);
        $yellow = (float) config('eis.target.yellow_below', 90);
        $achievement = (float) $metrics['target_achievement'];

        if ($achievement < $red) {
            $this->upsertException($employee, $period, 'target_achievement', 'danger',
                'Capaian target hanya '.number_format($achievement, 2).'% (di bawah '.number_format($red, 0).'%).');
        } elseif ($achievement < $yellow) {
            $this->upsertException($employee, $period, 'target_achievement', 'warning',
                'Capaian target '.number_format($achievement, 2).'% (di bawah '.number_format($yellow, 0).'%).');
        }

        if ((float) $metrics['rejection_rate'] > (float) config('eis.exception.rejected_rate_above', 50)) {
            $this->upsertException($employee, $period, 'restock_rejected', 'danger',
                'Restock ditolak '.number_format((float) $metrics['rejection_rate'], 2).'% pada periode ini.');
        }

        if ((float) $metrics['avg_response_hours'] > (float) config('eis.exception.response_hours_above', 24)) {
            $this->upsertException($employee, $period, 'slow_response', 'warning',
                'Rata-rata respons '.number_format((float) $metrics['avg_response_hours'], 2).' jam.');
        }

        if (! $metrics['has_recent_activity']) {
            $this->upsertException($employee, $period, 'inactive', 'danger',
                'Tidak ada aktivitas dalam '.config('eis.exception.inactive_days', 7).' hari terakhir.');
        }
    }

    private function upsertException(
        Employee $employee,
        string $period,
        string $type,
        string $severity,
        string $message
    ): void {
        $exception = EisException::query()->firstOrNew([
            'employee_id' => $employee->id,
            'period' => $period,
            'type' => $type,
        ]);

        if ($exception->exists && $exception->resolved_at !== null) {
            return;
        }

        $exception->forceFill(['severity' => $severity, 'message' => $message, 'resolved_at' => null])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function trendForEmployees(Collection $employees, string $period): array
    {
        $period = $this->normalizePeriod($period);
        [$start, $end] = $this->periodBounds($period);

        $labels = [];
        $cursor = clone $start;

        while ($cursor->lte($end)) {
            $labels[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $series = [];
        foreach ($employees as $employee) {
            $totals = array_fill(0, count($labels), 0.0);

            foreach ($this->transactionRows($employee, $period) as $transaction) {
                $day = Carbon::parse($transaction->date)->toDateString();
                $index = array_search($day, $labels, true);

                if ($index !== false) {
                    $totals[$index] += (float) $transaction->total;
                }
            }

            $series[] = [
                'employee_id' => (int) $employee->id,
                'name' => $employee->name,
                'data' => array_map(fn (float $value) => round($value, 2), $totals),
            ];
        }

        return ['labels' => $labels, 'datasets' => $series];
    }

    /**
     * @param  Collection<int, EisCriterion>  $criteria
     * @return array<int, array<int, float>>
     */
    private function ahpMatrixFromCriteria(Collection $criteria): array
    {
        if ($criteria->isEmpty()) {
            return [];
        }

        $weights = $criteria->map(fn (EisCriterion $item) => max((float) $item->weight, 0.0001))->all();
        $matrix = [];

        foreach ($weights as $row => $rowWeight) {
            foreach ($weights as $column => $columnWeight) {
                $matrix[$row][$column] = $row === $column
                    ? 1.0
                    : round($rowWeight / $columnWeight, 4);
            }
        }

        return $matrix;
    }
}
