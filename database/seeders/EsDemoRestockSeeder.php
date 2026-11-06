<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Restock request demo (berbagai status + selisih waktu persetujuan) untuk ES/OAS.
 * Idempoten, tidak menghapus data lama.
 */
class EsDemoRestockSeeder extends Seeder
{
    public function run(): void
    {
        if (RestockRequest::query()->count() >= 8) {
            $this->command?->info('Restock request demo sudah cukup, seeder dilewati.');

            return;
        }

        $products = Product::query()->orderBy('id')->limit(4)->get();
        if ($products->isEmpty()) {
            return;
        }

        $staff = User::query()->where('role', 'staff')->first() ?? User::query()->first();
        $employeeId = $staff
            ? (int) (Employee::query()->where('user_id', $staff->id)->value('id')
                ?? Employee::query()->insertGetId(['user_id' => $staff->id, 'name' => $staff->name, 'position' => 'Kasir', 'created_at' => now(), 'updated_at' => now()]))
            : (int) (Employee::query()->value('id') ?? 1);

        $rows = [
            ['status' => 'pending', 'days_ago' => 4, 'approved_after_hours' => null],   // >2 hari, trigger alert
            ['status' => 'pending', 'days_ago' => 1, 'approved_after_hours' => null],
            ['status' => 'approved', 'days_ago' => 10, 'approved_after_hours' => 6],
            ['status' => 'approved', 'days_ago' => 7, 'approved_after_hours' => 30],
            ['status' => 'rejected', 'days_ago' => 5, 'approved_after_hours' => 12],
            ['status' => 'approved', 'days_ago' => 20, 'approved_after_hours' => 48],
        ];

        foreach ($rows as $i => $row) {
            $product = $products[$i % $products->count()];
            $created = Carbon::now()->subDays($row['days_ago'])->startOfHour();
            $updated = $row['approved_after_hours'] !== null
                ? $created->copy()->addHours($row['approved_after_hours'])
                : $created->copy();

            RestockRequest::unguarded(fn () => RestockRequest::query()->create([
                'product_id' => $product->id,
                'employee_id' => $employeeId,
                'jumlah_restock' => 10 + ($i * 5),
                'status' => $row['status'],
                'catatan' => 'Demo ES/OAS',
                'created_at' => $created,
                'updated_at' => $updated,
            ]));
        }
    }
}
