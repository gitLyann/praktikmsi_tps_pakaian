<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\RestockRecommendationService;
use Database\Seeders\DssDemoTransactionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DssDemoTransactionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_transaksi_demo_90_hari_dengan_variasi_status(): void
    {
        Product::factory()->create(['stock' => 20]);
        Product::factory()->create(['stock' => 15]);

        $this->seed(DssDemoTransactionSeeder::class);

        $transactions = Transaction::query()->get();
        $this->assertGreaterThanOrEqual(60, $transactions->count());
        $this->assertGreaterThan(0, $transactions->where('status', 'completed')->count());
        $this->assertGreaterThan(0, $transactions->where('status', '!=', 'completed')->count());

        // Tiap transaksi punya minimal satu detail barang.
        $this->assertSame(0, $transactions->filter(fn ($transaction) => $transaction->details()->count() === 0)->count());
        $this->assertGreaterThanOrEqual($transactions->count(), TransactionDetail::query()->count());

        $oldest = $transactions->min('date');
        $this->assertTrue($oldest->greaterThanOrEqualTo(now()->subDays(91)->startOfDay()));
    }

    public function test_seeder_adalah_idempoten(): void
    {
        Product::factory()->create(['stock' => 20]);

        $this->seed(DssDemoTransactionSeeder::class);
        $first = Transaction::query()->count();

        $this->seed(DssDemoTransactionSeeder::class);

        $this->assertSame($first, Transaction::query()->count());
    }

    public function test_transaksi_demo_membuat_rekomendasi_restock_tersedia(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->seed(DssDemoTransactionSeeder::class);

        $analysis = app(RestockRecommendationService::class)->analyze();

        $item = collect($analysis['items'])->first(fn (array $item): bool => $item['product']->id === $product->id);

        $this->assertNotNull($item);
        $this->assertGreaterThan(0, $item['avg_daily_sales']);
        $this->assertTrue($item['needs_restock']);
    }
}
