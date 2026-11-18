<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Employee;
use App\Models\EisCriterion;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\StaffActivityLog;
use App\Models\StaffTarget;
use App\Models\Transaction;
use App\Models\User;
use App\Services\EisActivityLogger;
use App\Services\EisService;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EisDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (config('eis.criteria') as $definition) {
            EisCriterion::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'type' => $definition['type'],
                    'weight' => $definition['weight'],
                ]
            );
        }

        $logger = app(EisActivityLogger::class);
        $staff = $this->staffMembers();
        $customer = $this->customer();
        $product = $this->product();
        $start = now()->subDays(89)->startOfDay();

        $transactionExists = Transaction::query()
            ->where('payment_method', 'EIS Demo')
            ->where('date', '>=', $start)
            ->exists();

        if (! $transactionExists) {
            foreach ($staff as $index => $employee) {
                for ($day = 0; $day < 90; $day++) {
                    if (($day + $index) % 3 !== 0) {
                        continue;
                    }

                    $date = (clone $start)->addDays($day)->setTime(9 + ($index % 8), 15);
                    $times = ($day + $index) % 11 === 0 ? 2 : 1;

                    for ($time = 0; $time < $times; $time++) {
                        $transaction = $this->createTransaction(
                            $customer,
                            $employee,
                            $product,
                            200000 + ((($day * 37) + ($index * 53) + ($time * 17)) % 20) * 25000,
                            (clone $date)->addHours($time)
                        );

                        $logger->logTransaction($transaction);
                    }
                }
            }
        }

        foreach ($staff as $index => $employee) {
            $this->seedRestocks($employee, $product, $index, $logger);
            $this->seedEvaluations($employee, $index);
        }

        foreach (range(2, 0) as $monthsAgo) {
            $period = now()->subMonths($monthsAgo)->format('Y-m');

            foreach ($staff as $index => $employee) {
                StaffTarget::query()->updateOrCreate(
                    ['employee_id' => $employee->id, 'period' => $period],
                    [
                        'sales_target' => 12000000 + $index * 2500000,
                        'transaction_target' => 20 + $index * 5,
                    ]
                );
            }

            app(EisService::class)->buildSnapshots($period);
        }
    }

    /**
     * @return array<int, Employee>
     */
    private function staffMembers(): array
    {
        $names = ['Rani Pramesti', 'Dimas Saputra', 'Nadia Kusuma', 'Farhan Maulana'];
        $employees = [];

        foreach ($names as $index => $name) {
            $user = User::unguarded(fn () => User::firstOrCreate(
                ['email' => 'eis-staff-'.($index + 1).'@example.test'],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => 'staff',
                ]
            ));

            $employees[] = Employee::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['name' => $name, 'position' => 'Kasir']
            );
        }

        return $employees;
    }

    private function customer(): User
    {
        return User::unguarded(fn () => User::firstOrCreate(
            ['email' => 'eis-customer@example.test'],
            [
                'name' => 'Pelanggan Demo EIS',
                'password' => Hash::make('password'),
                'role' => 'pelanggan',
            ]
        ));
    }

    private function product(): Product
    {
        $category = Category::query()->firstOrCreate(['name' => 'EIS Demo']);

        return Product::unguarded(fn () => Product::firstOrCreate(
            ['name' => 'Produk Demo EIS'],
            [
                'category_id' => $category->id,
                'price' => 150000,
                'stock' => 200,
                'description' => 'Produk untuk data demo modul EIS.',
            ]
        ));
    }

    private function createTransaction(User $customer, Employee $employee, Product $product, float $total, Carbon $date): Transaction
    {
        $mysql = DB::connection()->getDriverName() === 'mysql';
        $customerValue = $mysql
            ? $customer->id
            : (DB::table('customers')->where('user_id', $customer->id)->value('id')
                ?? DB::table('customers')->insertGetId([
                    'user_id' => $customer->id,
                    'name' => $customer->name,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]));

        return Transaction::unguarded(fn () => Transaction::create([
            'customer_id' => $customerValue,
            'employee_id' => app(EisActivityLogger::class)->transactionEmployeeValue($employee),
            'date' => $date,
            'total' => $total,
            'payment_method' => 'EIS Demo',
            'status' => 'completed',
            'created_at' => $date,
            'updated_at' => $date,
        ]));
    }

    private function seedRestocks(Employee $employee, Product $product, int $index, EisActivityLogger $logger): void
    {
        if (RestockRequest::query()->where('employee_id', $employee->id)->where('catatan', 'Demo EIS')->exists()) {
            return;
        }

        $statuses = ['approved', 'approved', 'rejected', 'approved', 'pending', 'rejected'];

        foreach ($statuses as $step => $status) {
            $createdAt = now()->subDays(28 - ($step * 5) - $index)->setTime(10, 0);
            $updatedAt = $status === 'pending' ? $createdAt : (clone $createdAt)->addHours(2 + (($step + $index) % 20));

            $request = RestockRequest::unguarded(fn () => RestockRequest::create([
                'product_id' => $product->id,
                'employee_id' => $employee->id,
                'jumlah_restock' => 5 + $step + $index,
                'status' => $status,
                'catatan' => 'Demo EIS',
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]));

            $logger->logRestockRequested($request);
            $logger->logRestockDecided($request);
        }
    }

    private function seedEvaluations(Employee $employee, int $index): void
    {
        for ($step = 1; $step <= 3; $step++) {
            StaffActivityLog::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'activity_type' => 'supplier_evaluated',
                    'reference_type' => 'eis_demo_evaluation',
                    'reference_id' => $step,
                ],
                [
                    'occurred_at' => now()->subDays(60 - ($step * 12) - $index)->setTime(11, 30),
                    'meta' => ['source' => 'seed:eis-demo'],
                ]
            );
        }
    }
}
