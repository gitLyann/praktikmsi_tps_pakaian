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

    public function test_seeder_membuat_transaksi_serta_detail_selama_30_hari(): void
    {
        Product::factory()->create(['stock' => 20]);
        Product::factory()->create(['stock' => 15]);

        $this->seed(DssDemoTransactionSeeder::class);

        $transactions = Transaction::query()->where('status', 'completed')->get();

        $this->assertGreaterThanOrEqual(60, $transactions->count());

        // Tiap transaksi punya minimal satu detail barang.
        $this->assertSame(0, $transactions->filter(fn ($transaction) => $transaction->details()->count() === 0)->count());
        $this->assertGreaterThanOrEqual($transactions->count(), TransactionDetail::query()->count());

        $oldest = $transactions->min('date');
        $newest = $transactions->max('date');

        $this->assertTrue($oldest->between(now()->subDays(31)->startOfDay(), now()->subDays(29)->endOfDay()));
        $this->assertTrue($newest->between(now()->subDays(1)->startOfDay(), now()->endOfDay()));
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
