<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DssRestockRecommendation;
use App\Models\DssRun;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\RestockRequest;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\AhpService;
use App\Services\RestockRecommendationService;
use App\Services\VikorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestockRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RestockRecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RestockRecommendationService(
            app(AhpService::class),
            app(VikorService::class)
        );
    }

    public function test_menghitung_safety_stock_reorder_point_dan_jumlah_pemesanan(): void
    {
        // 60 pcs terjual dalam 30 hari = 2 pcs/hari.
        // Lead time 10 hari -> demand 20, safety stock 5, reorder point 25.
        // Stok sekarang 5 -> (25 + 5 - 5) = 25 -> dibulatkan ke MOQ 20 -> 40 pcs.
        [$product, $supplier] = $this->makeProductWithSupplier(stock: 5, sold: 60, leadTime: 10, minOrderQty: 20);

        $result = $this->service->analyze();

        $this->assertCount(1, $result['items']);

        $item = $result['items']->first();

        $this->assertSame($product->id, $item['product']->id);
        $this->assertSame($supplier->id, $item['supplier']->id);
        $this->assertSame(60, $item['quantity_sold']);
        $this->assertEqualsWithDelta(2.0, $item['avg_daily_sales'], 1e-6);
        $this->assertEqualsWithDelta(20.0, $item['demand_during_lead_time'], 1e-6);
        $this->assertSame(5, $item['safety_stock']);
        $this->assertSame(25, $item['reorder_point']);
        $this->assertSame(40, $item['recommended_qty']);
        $this->assertEqualsWithDelta(40 * $item['unit_price'], $item['estimated_cost'], 0.01);
        $this->assertTrue($item['needs_restock']);
    }

    public function test_produk_tanpa_penjualan_tidak_disarankan_restock(): void
    {
        [$product] = $this->makeProductWithSupplier(stock: 30, sold: 0, leadTime: 7, minOrderQty: 10);

        $result = $this->service->analyze();

        $item = $result['items']->first();

        $this->assertSame(0, $item['quantity_sold']);
        $this->assertEqualsWithDelta(0.0, $item['avg_daily_sales'], 1e-6);
        $this->assertSame(0, $item['safety_stock']);
        $this->assertSame(0, $item['reorder_point']);
        $this->assertSame(0, $item['recommended_qty']);
        $this->assertFalse($item['needs_restock']);
        $this->assertSame($product->id, $item['product']->id);
    }

    public function test_transaksi_bukan_completed_diabaikan(): void
    {
        [$product] = $this->makeProductWithSupplier(stock: 5, sold: 0, leadTime: 7, minOrderQty: 10);

        $this->createSale($product, quantity: 40, status: 'cancelled');

        $result = $this->service->analyze();

        $this->assertSame(0, $result['items']->first()['quantity_sold']);
    }

    public function test_transaksi_di_luar_periode_analisis_diabaikan(): void
    {
        [$product] = $this->makeProductWithSupplier(stock: 5, sold: 0, leadTime: 7, minOrderQty: 10);

        $this->createSale($product, quantity: 40, status: 'completed', date: now()->subDays(60));

        $result = $this->service->analyze();

        $this->assertSame(0, $result['items']->first()['quantity_sold']);
    }

    public function test_periode_analisis_bisa_diubah(): void
    {
        [$product] = $this->makeProductWithSupplier(stock: 5, sold: 40, leadTime: 7, minOrderQty: 10);

        $result = $this->service->analyze(null, 60);

        $this->assertSame(60, $result['days']);
        $this->assertEqualsWithDelta(40 / 60, $result['items']->first()['avg_daily_sales'], 1e-4);
    }

    public function test_ringkasan_menjumlahkan_produk_yang_perlu_restock(): void
    {
        $this->makeProductWithSupplier(stock: 5, sold: 60, leadTime: 10, minOrderQty: 20);
        $this->makeProductWithSupplier(stock: 90, sold: 1, leadTime: 10, minOrderQty: 20);

        $result = $this->service->analyze();

        $this->assertSame(2, $result['summary']['products']);
        $this->assertSame(1, $result['summary']['need_restock']);
        $this->assertSame(40, $result['summary']['total_qty']);
    }

    public function test_menyimpan_rekomendasi_ke_tabel_dss_restock_recommendations(): void
    {
        [$product, $supplier] = $this->makeProductWithSupplier(stock: 5, sold: 60, leadTime: 10, minOrderQty: 20);

        $run = DssRun::create([
            'name' => 'Analisis Restock Uji',
            'type' => 'restock',
            'status' => 'completed',
        ]);

        $analysis = $this->service->analyze();
        $saved = $this->service->persist($run, $analysis);

        $this->assertCount(1, $saved);

        $recommendation = DssRestockRecommendation::query()->firstOrFail();

        $this->assertSame($run->id, $recommendation->dss_run_id);
        $this->assertSame($product->id, $recommendation->product_id);
        $this->assertSame($supplier->id, $recommendation->supplier_id);
        $this->assertSame(5, $recommendation->current_stock);
        $this->assertSame(25, $recommendation->reorder_point);
        $this->assertSame(5, $recommendation->safety_stock);
        $this->assertSame(40, $recommendation->recommended_qty);
        $this->assertSame('draft', $recommendation->status);
    }

    public function test_membuat_pengajuan_restock_dari_rekomendasi(): void
    {
        [$product, $supplier] = $this->makeProductWithSupplier(stock: 5, sold: 60, leadTime: 10, minOrderQty: 20);

        $run = DssRun::create([
            'name' => 'Analisis Restock Uji',
            'type' => 'restock',
            'status' => 'completed',
        ]);

        $employee = Employee::create(['name' => 'Budi Perkasa', 'position' => 'Staff Gudang']);

        $saved = $this->service->persist($run, $this->service->analyze());

        $request = $this->service->toRestockRequest($saved->first(), $employee->id);

        $this->assertInstanceOf(RestockRequest::class, $request);
        $this->assertSame($product->id, $request->product_id);
        $this->assertSame($employee->id, $request->employee_id);
        $this->assertSame(40, $request->jumlah_restock);
        $this->assertSame('pending', $request->status);
        $this->assertSame($supplier->id, $request->supplier_id);
        $this->assertSame($run->id, $request->dss_run_id);
        $this->assertStringContainsString('reorder point', (string) $request->catatan);
    }

    public function test_peringkat_vikor_menentukan_pemasok_yang_dipakai(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 5]);

        $supplierA = Supplier::factory()->create(['name' => 'Konveksi A']);
        $supplierB = Supplier::factory()->create(['name' => 'Konveksi B']);

        ProductSupplier::factory()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplierA->id,
            'price' => 10000,
            'min_order_qty' => 20,
            'lead_time_days' => 10,
            'is_preferred' => true,
        ]);

        ProductSupplier::factory()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplierB->id,
            'price' => 12000,
            'min_order_qty' => 25,
            'lead_time_days' => 14,
            'is_preferred' => false,
        ]);

        $this->createSale($product, quantity: 60);

        $result = $this->service->analyze(null, 30, [$supplierB->id => 1, $supplierA->id => 2]);

        $item = $result['items']->first();

        $this->assertSame($supplierB->id, $item['supplier']->id);
        $this->assertSame(14, $item['lead_time_days']);
        $this->assertSame(25, $item['min_order_qty']);
    }

    public function test_pemasok_preferred_dipakai_bila_ranking_vikor_tidak_ada(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 5]);

        $supplierA = Supplier::factory()->create(['name' => 'Konveksi A']);
        $supplierB = Supplier::factory()->create(['name' => 'Konveksi B']);

        ProductSupplier::factory()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplierA->id,
            'lead_time_days' => 10,
        ]);

        ProductSupplier::factory()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplierB->id,
            'lead_time_days' => 14,
            'is_preferred' => true,
        ]);

        $result = $this->service->analyze();

        $this->assertSame($supplierB->id, $result['items']->first()['supplier']->id);
    }

    public function test_produk_tanpa_pemasok_tetap_dihitung_dengan_lead_time_default(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => 2]);

        $this->createSale($product, quantity: 30);

        $result = $this->service->analyze();

        $item = $result['items']->first();

        $this->assertNull($item['supplier']);
        $this->assertSame(RestockRecommendationService::DEFAULT_LEAD_TIME_DAYS, $item['lead_time_days']);
        $this->assertSame(1, $item['min_order_qty']);
        $this->assertTrue($item['needs_restock']);
    }

    /**
     * @return array{0: Product, 1: Supplier}
     */
    private function makeProductWithSupplier(int $stock, int $sold, int $leadTime, int $minOrderQty): array
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'stock' => $stock]);

        $supplier = Supplier::factory()->create([
            'lead_time_days' => $leadTime,
            'min_order_qty' => $minOrderQty,
        ]);

        ProductSupplier::factory()->create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'price' => 25000,
            'min_order_qty' => $minOrderQty,
            'lead_time_days' => $leadTime,
            'is_preferred' => true,
        ]);

        if ($sold > 0) {
            $this->createSale($product, quantity: $sold);
        }

        return [$product, $supplier];
    }

    private function createSale(Product $product, int $quantity, string $status = 'completed', ?string $date = null): Transaction
    {
        $transaction = Transaction::create([
            'date' => $date ?? now(),
            'total' => $quantity * $product->price,
            'payment_method' => 'cash',
            'status' => $status,
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'subtotal' => $quantity * $product->price,
        ]);

        return $transaction;
    }
}
