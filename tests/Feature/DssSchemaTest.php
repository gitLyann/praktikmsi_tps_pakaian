<?php

namespace Tests\Feature;

use App\Models\DssCriterion;
use App\Models\DssCriterionComparison;
use App\Models\DssRestockRecommendation;
use App\Models\DssRun;
use App\Models\DssSupplierScore;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\RestockRequest;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_pemasok_dan_penawaran_produk_tersimpan(): void
    {
        $supplier = Supplier::factory()->create([
            'code' => 'SUP-TEST',
            'lead_time_days' => 9,
            'min_order_qty' => 30,
        ]);

        $product = Product::factory()->create();

        $offer = ProductSupplier::create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'price' => 90000,
            'min_order_qty' => 30,
            'lead_time_days' => 9,
            'is_preferred' => true,
        ]);

        $this->assertSame($supplier->id, $offer->supplier->id);
        $this->assertSame($product->id, $offer->product->id);
        $this->assertTrue($supplier->fresh()->productSuppliers->isNotEmpty());
        $this->assertTrue($product->fresh()->suppliers->contains($supplier));
        $this->assertSame(9, (int) $supplier->lead_time_days);
    }

    public function test_kriteria_dan_matriks_perbandingan_bisa_disimpan(): void
    {
        $harga = DssCriterion::factory()->cost()->create(['code' => 'harga', 'weight' => 0.6]);
        $mutu = DssCriterion::factory()->benefit()->create(['code' => 'mutu', 'weight' => 0.4]);

        $comparison = DssCriterionComparison::create([
            'criterion_a_id' => $harga->id,
            'criterion_b_id' => $mutu->id,
            'value' => 1.5,
        ]);

        $this->assertSame($harga->id, $comparison->criterionA->id);
        $this->assertSame($mutu->id, $comparison->criterionB->id);
        $this->assertFalse($harga->isBenefit());
        $this->assertTrue($mutu->isBenefit());
        $this->assertSame(0.6, $harga->fresh()->weight);
    }

    public function test_run_analisis_skor_dan_rekomendasi_tersimpan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();
        $criterion = DssCriterion::factory()->benefit()->create();
        $product = Product::factory()->create(['stock' => 3]);

        $run = DssRun::create([
            'name' => 'Analisis VIKOR Q3',
            'type' => 'vikor',
            'status' => 'completed',
            'created_by' => $admin->id,
            'criteria_count' => 1,
            'lambda_max' => 1,
            'ci' => 0,
            'cr' => 0,
            'ri' => 0,
            'is_consistent' => true,
            'payload' => ['ranking' => [['supplier' => $supplier->name, 'q' => 0.12]]],
        ]);

        $score = DssSupplierScore::create([
            'dss_run_id' => $run->id,
            'supplier_id' => $supplier->id,
            'dss_criterion_id' => $criterion->id,
            'score' => 4.5,
            'weight' => 1,
        ]);

        $recommendation = DssRestockRecommendation::create([
            'dss_run_id' => $run->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'current_stock' => 3,
            'avg_daily_sales' => 1.5,
            'lead_time_days' => 7,
            'safety_stock' => 3,
            'reorder_point' => 14,
            'recommended_qty' => 25,
            'estimated_cost' => 2250000,
            'status' => 'draft',
        ]);

        $this->assertSame($admin->id, $run->creator->id);
        $this->assertSame($run->id, $score->dssRun->id);
        $this->assertSame($supplier->id, $score->supplier->id);
        $this->assertSame($criterion->id, $score->criterion->id);
        $this->assertTrue($recommendation->needsRestock());
        $this->assertSame('VIKOR', $run->typeLabel());
        $this->assertIsArray($run->fresh()->payload);
        $this->assertSame($supplier->name, $run->fresh()->payload['ranking'][0]['supplier']);
    }

    public function test_pengajuan_restock_bisa_dihubungkan_ke_pemasok_dan_run_dss(): void
    {
        $supplier = Supplier::factory()->create();
        $run = DssRun::create(['name' => 'Run uji', 'type' => 'restock']);
        $product = Product::factory()->create();
        $employeeId = \DB::table('employees')->insertGetId([
            'user_id' => null,
            'name' => 'Staff Uji',
            'position' => 'Gudang',
        ]);

        $request = RestockRequest::create([
            'product_id' => $product->id,
            'employee_id' => $employeeId,
            'jumlah_restock' => 20,
            'status' => 'pending',
            'supplier_id' => $supplier->id,
            'dss_run_id' => $run->id,
        ]);

        $this->assertSame($supplier->id, $request->fresh()->supplier->id);
        $this->assertSame($run->id, $request->fresh()->dssRun->id);
        $this->assertSame(1, RestockRequest::where('dss_run_id', $run->id)->count());
    }
}
