<?php

namespace App\Services;

use App\Models\DssRun;
use App\Models\Employee;
use App\Models\RestockRequest;
use App\Models\StaffActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EisActivityLogger
{
    public function resolveEmployeeIdFromTransaction(int $transactionEmployeeId): ?int
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return Employee::query()->where('user_id', $transactionEmployeeId)->value('id');
        }

        return Employee::query()->whereKey($transactionEmployeeId)->value('id');
    }

    public function transactionEmployeeValue(Employee $employee): int
    {
        return DB::connection()->getDriverName() === 'mysql'
            ? (int) $employee->user_id
            : (int) $employee->id;
    }

    public function logTransaction(Model $transaction): void
    {
        if ($transaction->status !== 'completed' || ! $transaction->employee_id) {
            return;
        }

        $employeeId = $this->resolveEmployeeIdFromTransaction((int) $transaction->employee_id);

        if ($employeeId === null) {
            return;
        }

        $this->write(
            $employeeId,
            'transaction_processed',
            'transaction',
            (int) $transaction->getKey(),
            $transaction->date ?? $transaction->created_at ?? now(),
            ['total' => (float) $transaction->total]
        );
    }

    public function logRestockRequested(RestockRequest $request): void
    {
        $this->write(
            (int) $request->employee_id,
            'restock_requested',
            'restock_request',
            (int) $request->getKey(),
            $request->created_at ?? now(),
            ['jumlah' => (int) $request->jumlah_restock]
        );
    }

    public function logRestockDecided(RestockRequest $request): void
    {
        if (! in_array($request->status, ['approved', 'rejected'], true)) {
            return;
        }

        $createdAt = $request->created_at ?? now();
        $updatedAt = $request->updated_at ?? $createdAt;

        $this->write(
            (int) $request->employee_id,
            'restock_decided',
            'restock_request',
            (int) $request->getKey(),
            $updatedAt,
            [
                'status' => $request->status,
                'response_hours' => max(0, round($createdAt->diffInHours($updatedAt), 2)),
            ]
        );
    }

    public function logSupplierEvaluated(Model $score): void
    {
        $run = DssRun::query()->find($score->dss_run_id);
        $creatorId = $run?->created_by;

        if ($creatorId === null) {
            return;
        }

        $employeeId = Employee::query()->where('user_id', $creatorId)->value('id');

        if ($employeeId === null) {
            return;
        }

        $this->write(
            (int) $employeeId,
            'supplier_evaluated',
            'dss_supplier_score',
            (int) $score->getKey(),
            $score->created_at ?? now(),
            ['dss_run_id' => (int) $score->dss_run_id]
        );
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function write(
        int $employeeId,
        string $activityType,
        string $referenceType,
        int $referenceId,
        mixed $occurredAt,
        ?array $meta = null
    ): void {
        try {
            StaffActivityLog::query()->firstOrCreate(
                [
                    'employee_id' => $employeeId,
                    'activity_type' => $activityType,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ],
                [
                    'occurred_at' => $occurredAt,
                    'meta' => $meta,
                ]
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
