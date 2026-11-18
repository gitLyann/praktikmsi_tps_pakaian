<?php

namespace App\Console\Commands;

use App\Models\DssSupplierScore;
use App\Models\RestockRequest;
use App\Models\StaffActivityLog;
use App\Models\Transaction;
use App\Services\EisActivityLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('eis:backfill')]
#[Description('Bangun staff_activity_logs dari transaksi, restock, dan penilaian supplier yang sudah ada.')]
class EisBackfillCommand extends Command
{
    public function handle(EisActivityLogger $logger): int
    {
        $before = StaffActivityLog::query()->count();

        Transaction::query()
            ->where('status', 'completed')
            ->whereNotNull('employee_id')
            ->chunkById(200, fn ($rows) => $rows->each(fn ($row) => $logger->logTransaction($row)));

        RestockRequest::query()->chunkById(200, function ($rows) use ($logger) {
            foreach ($rows as $row) {
                $logger->logRestockRequested($row);
                $logger->logRestockDecided($row);
            }
        });

        DssSupplierScore::query()->chunkById(200, fn ($rows) => $rows->each(fn ($row) => $logger->logSupplierEvaluated($row)));

        $after = StaffActivityLog::query()->count();

        $this->info('Backfill EIS selesai. Aktivitas baru: '.($after - $before).', total: '.$after.'.');

        return self::SUCCESS;
    }
}
