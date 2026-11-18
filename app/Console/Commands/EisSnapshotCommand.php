<?php

namespace App\Console\Commands;

use App\Services\EisService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('eis:snapshot {period? : Periode Y-m, default bulan ini}')]
#[Description('Hitung snapshot kinerja staf dan ranking VIKOR untuk satu periode.')]
class EisSnapshotCommand extends Command
{
    public function handle(EisService $eis): int
    {
        $period = $eis->normalizePeriod($this->argument('period'));
        $snapshots = $eis->buildSnapshots($period);

        $this->info('Snapshot EIS periode '.$period.' selesai. Staf: '.$snapshots->count().'.');

        return self::SUCCESS;
    }
}
