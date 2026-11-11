<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chatbot;
use App\Models\EsKpiTarget;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function customerIdFor(User $user): int
    {
        $row = DB::selectOne("SELECT name FROM sqlite_master WHERE type='table' AND name='customers'");
        if ($row) {
            return (int) (DB::table('customers')->where('user_id', $user->id)->value('id')
                ?? DB::table('customers')->insertGetId(['user_id' => $user->id, 'name' => $user->name, 'created_at' => now(), 'updated_at' => now()]));
        }

        return (int) $user->id;
    }

    private function makeCompletedTransaction(User $customer, Product $product, float $total, string $payment, Carbon $date, string $status = 'completed'): Transaction
    {
        $tx = Transaction::unguarded(fn () => Transaction::create([
            'customer_id' => $this->customerIdFor($customer), 'employee_id' => null, 'date' => $date,
            'total' => $total, 'payment_method' => $payment, 'status' => $status,
            'created_at' => $date, 'updated_at' => $date,
        ]));
        TransactionDetail::unguarded(fn () => TransactionDetail::create([
            'transaction_id' => $tx->id, 'product_id' => $product->id, 'quantity' => 1,
            'subtotal' => $total, 'created_at' => $date, 'updated_at' => $date,
        ]));

        return $tx;
    }

    public function test_admin_can_access_es_dashboard_and_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.es'))->assertOk()->assertSee('Dashboard Eksekutif');
        $res = $this->actingAs($admin)->getJson(route('admin.es.data.summary', ['preset' => '30']));
        $res->assertOk()->assertJsonStructure(['revenue' => ['value', 'change_percent', 'direction'], 'transactions', 'period']);
        $chart = $this->actingAs($admin)->getJson(route('admin.es.data.chart', ['name' => 'revenue-trend', 'preset' => '30']));
        $chart->assertOk()->assertJsonStructure(['labels', 'datasets', 'meta']);
    }

    public function test_staff_and_pelanggan_forbidden_tamu_redirected(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $pelanggan = User::factory()->create(['role' => 'pelanggan']);
        $this->actingAs($staff)->get(route('admin.es'))->assertStatus(403);
        $this->actingAs($pelanggan)->get(route('admin.es.data.summary'))->assertStatus(403);
        auth()->logout();
        $this->get(route('admin.es'))->assertRedirect(route('login'));
        $this->getJson(route('admin.es.data.summary'))->assertStatus(401);
    }

    public function test_filter_validation_rejects_invalid_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->getJson(route('admin.es.data.summary', ['from' => '2026-01-10', 'to' => '2026-01-01']))
            ->assertStatus(422);
        $this->actingAs($admin)->getJson(route('admin.es.data.summary', ['from' => '2024-01-01', 'to' => '2026-01-01']))
            ->assertStatus(422);
    }

    public function test_revenue_counts_only_completed_and_groups_payment_labels(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cust = User::factory()->create(['role' => 'pelanggan']);
        $cat = Category::create(['name' => 'Atasan']);
        $p = Product::create(['category_id' => $cat->id, 'name' => 'Kaos', 'price' => 100000, 'stock' => 10]);
        $d = now()->subDays(2);
        $this->makeCompletedTransaction($cust, $p, 100000, 'E-Wallet', $d->copy());
        $this->makeCompletedTransaction($cust, $p, 50000, 'emoney_gopay', $d->copy());
        $this->makeCompletedTransaction($cust, $p, 75000, 'Transfer BCA', $d->copy());
        $this->makeCompletedTransaction($cust, $p, 125000, 'Transfer Bank', $d->copy());
        $this->makeCompletedTransaction($cust, $p, 999000, 'Cash', $d->copy(), 'pending'); // must be excluded

        $sum = $this->actingAs($admin)->getJson(route('admin.es.data.summary', ['preset' => '30']))->assertOk()->json();
        $this->assertEquals(350000.0, $sum['revenue']['value']);
        $this->assertEquals(4, $sum['transactions']['value']);
        $this->assertEquals(0, $sum['critical_stock']['value']); // stok 10 > ambang 5

        $pm = $this->actingAs($admin)->getJson(route('admin.es.data.chart', ['name' => 'payment-methods', 'preset' => '30']))->assertOk()->json();
        $idx = array_search('E-Wallet', $pm['labels'], true);
        $this->assertNotFalse($idx);
        $this->assertEquals(2, $pm['datasets'][0]['data'][$idx]); // legacy + new grouped
        $idxTransfer = array_search('Transfer Bank', $pm['labels'], true);
        $this->assertNotFalse($idxTransfer);
        $this->assertEquals(2, $pm['datasets'][0]['data'][$idxTransfer]); // 'Transfer BCA' + 'Transfer Bank' tergrup
    }

    public function test_alerts_target_and_avg_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cust = User::factory()->create(['role' => 'pelanggan']);
        $cat = Category::create(['name' => 'X']);
        $p = Product::create(['category_id' => $cat->id, 'name' => 'Kritis', 'price' => 10000, 'stock' => 1]);
        $employeeId = DB::table('employees')->insertGetId(['user_id' => $admin->id, 'name' => $admin->name, 'position' => 'Adm', 'created_at' => now(), 'updated_at' => now()]);
        RestockRequest::unguarded(fn () => RestockRequest::create([
            'product_id' => $p->id, 'employee_id' => $employeeId, 'jumlah_restock' => 5, 'status' => 'pending',
            'catatan' => 'x', 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5),
        ]));
        RestockRequest::unguarded(fn () => RestockRequest::create([
            'product_id' => $p->id, 'employee_id' => $employeeId, 'jumlah_restock' => 5, 'status' => 'approved',
            'catatan' => 'x', 'created_at' => now()->subHours(10), 'updated_at' => now()->subHours(6), // 4h
        ]));

        $alerts = $this->actingAs($admin)->getJson(route('admin.es.data.alerts', ['preset' => '30']))->assertOk()->json();
        $levels = array_column($alerts['items'], 'source');
        $this->assertContains('OAS', $levels);

        $sum = $this->actingAs($admin)->getJson(route('admin.es.data.summary', ['preset' => '30']))->assertOk()->json();
        $this->assertEquals(1, $sum['critical_stock']['value']); // stok 1 <= ambang 5

        $this->actingAs($admin)->postJson(route('admin.es.target.store'), ['period' => now()->format('Y-m'), 'revenue_target' => 5000000])
            ->assertOk()->assertJsonPath('ok', true);
        $t = EsKpiTarget::where('period', now()->format('Y-m'))->first();
        $this->assertNotNull($t);
        $this->actingAs($admin)->postJson(route('admin.es.target.store'), ['period' => now()->format('Y-m'), 'revenue_target' => 7000000])->assertOk();
        $this->assertEquals(7000000.0, (float) $t->fresh()->revenue_target);

        // KMS: percakapan chatbot nyata tercatat dan muncul di panel integrasi.
        Chatbot::create(['queries' => 'jam buka', 'replies' => 'Buka 09:00-21:00 WIB.']);
        $this->actingAs($admin)->postJson(route('chatbot.message'), ['message' => 'jam buka'])->assertOk();

        $integ = $this->actingAs($admin)->getJson(route('admin.es.data.integration', ['preset' => '30']))->assertOk()->json();
        $oas = collect($integ['systems'])->firstWhere('key', 'oas');
        $avg = collect($oas['metrics'])->firstWhere('format', 'hours')['value'];
        $this->assertEquals(4.0, $avg);
        $kms = collect($integ['systems'])->firstWhere('key', 'kms');
        $conversations = collect($kms['metrics'])->firstWhere('label', 'Percakapan (periode)');
        $this->assertEquals(1, $conversations['value']);
    }
}
