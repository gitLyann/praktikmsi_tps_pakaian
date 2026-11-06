<?php

namespace Database\Seeders;

use App\Models\EsKpiTarget;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Target omzet ES untuk bulan ini dan bulan lalu (idempoten).
 */
class EsKpiTargetSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->where('role', 'admin')->value('id');

        foreach ([now()->subMonth()->format('Y-m') => 15000000, now()->format('Y-m') => 20000000] as $period => $target) {
            EsKpiTarget::query()->updateOrCreate(
                ['period' => $period],
                ['revenue_target' => $target, 'created_by' => $adminId]
            );
        }
    }
}
