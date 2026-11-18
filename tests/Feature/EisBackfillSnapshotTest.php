<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EisCriterion;
use App\Models\Employee;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\StaffActivityLog;
use App\Models\StaffPerformanceSnapshot;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EisBackfillSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (config('eis.criteria') as $definition) {
            EisCriterion::query()->create([
                'code' => $definition['code'],
                'name' => $definition['name'],
                'type' => $definition['type'],
                'weight' => $definition['weight'],
            ]);
        }
    }

    private function customerIdFor(User $user): int
    {
        return (int) (DB::table('customers')->where('user_id', $user->id)->value('id')
            ?? DB::table('customers')->insertGetId([
                'user_id' => $user->id,
                'name' => $user->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
    }

    public function test_backfill_is_idempotent_and_uses_only_completed_sales(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $employee = Employee::query()->create(['user_id' => $user->id, 'name' => $user->name, 'position' => 'Kasir']);
        $customer = User::factory()->create(['role' => 'pelanggan']);
        $category = Category::query()->create(['name' => 'EIS Test']);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Kaos EIS', 'price' => 100000, 'stock' => 20]);
        $date = now()->subDays(2);

        foreach ([['completed', 100000], ['completed', 50000], ['pending', 999000]] as $index => [$status, $total]) {
            Transaction::unguarded(fn () => Transaction::create([
                'customer_id' => $this->customerIdFor($customer),
                'employee_id' => $employee->id,
                'date' => (clone $date)->addMinutes($index),
                'total' => $total,
                'payment_method' => 'EIS Test',
                'status' => $status,
            ]));
        }

        foreach ([['approved', now()->subHours(10), now()->subHours(6)], ['pending', now()->subHours(4), now()->subHours(4)]] as $index => [$status, $created, $updated]) {
            RestockRequest::unguarded(fn () => RestockRequest::create([
                'product_id' => $product->id,
                'employee_id' => $employee->id,
                'jumlah_restock' => 5 + $index,
                'status' => $status,
                'catatan' => 'EIS Test',
                'created_at' => $created,
                'updated_at' => $updated,
            ]));
        }

        StaffActivityLog::query()->create([
            'employee_id' => $employee->id,
            'activity_type' => 'supplier_evaluated',
            'reference_type' => 'dss_supplier_score',
            'reference_id' => 99,
            'occurred_at' => now()->subDays(1),
        ]);

        $this->artisan('eis:backfill')->assertExitCode(0);

        $this->assertSame(2, StaffActivityLog::query()->where('activity_type', 'transaction_processed')->count());
        $this->assertSame(2, StaffActivityLog::query()->where('activity_type', 'restock_requested')->count());
        $this->assertSame(1, StaffActivityLog::query()->where('activity_type', 'restock_decided')->count());
        $this->assertSame(6, StaffActivityLog::query()->count());

        $this->artisan('eis:backfill')->assertExitCode(0);
        $this->assertSame(6, StaffActivityLog::query()->count());
    }

    public function test_snapshot_uses_service_metrics_and_vikor_ranking(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $employee = Employee::query()->create(['user_id' => $user->id, 'name' => $user->name, 'position' => 'Kasir']);
        $customer = User::factory()->create(['role' => 'pelanggan']);
        $category = Category::query()->create(['name' => 'EIS Test']);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Kaos EIS', 'price' => 100000, 'stock' => 20]);
        $date = now()->subDays(1);

        Transaction::unguarded(fn () => Transaction::create([
            'customer_id' => $this->customerIdFor($customer),
            'employee_id' => $employee->id,
            'date' => $date,
            'total' => 150000,
            'payment_method' => 'EIS Test',
            'status' => 'completed',
        ]));

        RestockRequest::unguarded(fn () => RestockRequest::create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'jumlah_restock' => 5,
            'status' => 'approved',
            'catatan' => 'EIS Test',
            'created_at' => now()->subHours(10),
            'updated_at' => now()->subHours(6),
        ]));

        StaffActivityLog::query()->create([
            'employee_id' => $employee->id,
            'activity_type' => 'supplier_evaluated',
            'reference_type' => 'dss_supplier_score',
            'reference_id' => 100,
            'occurred_at' => now()->subHours(2),
        ]);

        $period = now()->format('Y-m');
        $this->artisan('eis:snapshot', ['period' => $period])->assertExitCode(0);

        $snapshot = StaffPerformanceSnapshot::query()
            ->where('employee_id', $employee->id)
            ->where('period', $period)
            ->firstOrFail();

        $this->assertEquals(150000.0, (float) $snapshot->total_sales);
        $this->assertSame(1, (int) $snapshot->transactions_count);
        $this->assertSame(1, (int) $snapshot->restock_approved);
        $this->assertSame(1, (int) $snapshot->evaluations_count);
        $this->assertSame(1, (int) $snapshot->rank);
        $this->assertNotNull($snapshot->performance_score);

        $this->artisan('eis:snapshot', ['period' => $period])->assertExitCode(0);
        $this->assertSame(1, StaffPerformanceSnapshot::query()->where('period', $period)->count());
    }

    public function test_observer_logs_completed_transaction_and_restock_decision(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $employee = Employee::query()->create(['user_id' => $user->id, 'name' => $user->name, 'position' => 'Kasir']);
        $customer = User::factory()->create(['role' => 'pelanggan']);
        $category = Category::query()->create(['name' => 'EIS Test']);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Kaos EIS', 'price' => 100000, 'stock' => 20]);

        Transaction::unguarded(fn () => Transaction::create([
            'customer_id' => $this->customerIdFor($customer),
            'employee_id' => $employee->id,
            'date' => now(),
            'total' => 75000,
            'payment_method' => 'EIS Observer Test',
            'status' => 'completed',
        ]));

        $this->assertSame(1, StaffActivityLog::query()->where('activity_type', 'transaction_processed')->count());

        $request = RestockRequest::unguarded(fn () => RestockRequest::create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'jumlah_restock' => 5,
            'status' => 'pending',
            'catatan' => 'EIS Observer Test',
        ]));

        $this->assertSame(1, StaffActivityLog::query()->where('activity_type', 'restock_requested')->count());

        $request->forceFill(['status' => 'approved'])->save();

        $this->assertSame(1, StaffActivityLog::query()->where('activity_type', 'restock_decided')->count());
    }
}
