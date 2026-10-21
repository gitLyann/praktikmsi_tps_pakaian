<?php

namespace App\Services;

use App\Models\DssRestockRecommendation;
use App\Models\DssRun;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\RestockRequest;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekomendasi restock berbasis DSS.
 *
 * Perhitungan memakai data penjualan nyata:
 * - Rata-rata penjualan harian = total pcs terjual (hanya transaksi `completed`)
 *   dibagi jumlah hari analisis.
 * - Demand selama lead time = rata-rata harian x lead time pemasok.
 * - Safety stock = ceil(demand lead time x 0.25).
 * - Reorder point = ceil(demand lead time) + safety stock.
 * - Jumlah pemesanan yang disarankan = (reorder point + safety stock - stok sekarang),
 *   dibulatkan ke atas mengikuti minimum order pemasok.
 */
class RestockRecommendationService
{
    /** Rentang analisis default (hari). */
    public const ANALYSIS_DAYS = 30;

    /** Faktor keamanan untuk safety stock. */
    public const SAFETY_FACTOR = 0.25;

    /** Lead time fallback bila produk belum punya pemasok. */
    public const DEFAULT_LEAD_TIME_DAYS = 7;

    public function __construct(
        private readonly AhpService $ahpService,
        private readonly VikorService $vikorService
    ) {}

    /**
     * Menghitung rekomendasi restock untuk setiap produk.
     *
     * @param  Collection<int, Product>|null  $products  Null = semua produk.
     * @param  int  $days  Periode analisis (hari).
     * @param  array<int, int>|null  $supplierRanking  [id_pemasok] = peringkat VIKOR (1 = terbaik).
     * @return array{generated_at: string, days: int, items: Collection<int, array>, summary: array}
     */
    public function analyze(
        ?Collection $products = null,
        int $days = self::ANALYSIS_DAYS,
        ?array $supplierRanking = null
    ): array {
        $days = max(1, $days);

        $products = ($products ?? Product::query()->with('productSuppliers.supplier')->get())
            ->loadMissing('productSuppliers.supplier');

        $sold = $this->salesPerProduct($days);

        $items = [];

        foreach ($products as $product) {
            $quantitySold = (int) ($sold[$product->id] ?? 0);
            $avgDailySales = $quantitySold / $days;

            $offer = $this->pickSupplierOffer($product, $supplierRanking);

            $leadTime = (int) ($offer['lead_time_days'] ?? self::DEFAULT_LEAD_TIME_DAYS);
            $minOrderQty = max(1, (int) ($offer['min_order_qty'] ?? 1));
            $unitPrice = (float) ($offer['price'] ?? 0);

            $demandDuringLeadTime = $avgDailySales * $leadTime;
            $safetyStock = (int) ceil($demandDuringLeadTime * self::SAFETY_FACTOR);
            $reorderPoint = (int) ceil($demandDuringLeadTime) + $safetyStock;

            $currentStock = (int) $product->stock;
            $rawQty = max(0, $reorderPoint + $safetyStock - $currentStock);

            // Bulatkan ke atas mengikuti minimum order pemasok.
            $recommendedQty = $rawQty > 0
                ? (int) (ceil($rawQty / $minOrderQty) * $minOrderQty)
                : 0;

            $items[] = [
                'product' => $product,
                'supplier' => $offer['supplier'],
                'offer' => $offer['offer'],
                'current_stock' => $currentStock,
                'quantity_sold' => $quantitySold,
                'avg_daily_sales' => round($avgDailySales, 4),
                'lead_time_days' => $leadTime,
                'min_order_qty' => $minOrderQty,
                'unit_price' => $unitPrice,
                'demand_during_lead_time' => round($demandDuringLeadTime, 4),
                'safety_stock' => $safetyStock,
                'reorder_point' => $reorderPoint,
                'recommended_qty' => $recommendedQty,
                'estimated_cost' => round($recommendedQty * $unitPrice, 2),
                'needs_restock' => $currentStock <= $reorderPoint,
            ];
        }

        return [
            'generated_at' => now()->toDateTimeString(),
            'days' => $days,
            'items' => collect($items),
            'summary' => $this->summarize($items),
        ];
    }

    /**
     * Menyimpan hasil rekomendasi ke tabel `dss_restock_recommendations`.
     *
     * @param  array{generated_at: string, days: int, items: Collection<int, array>, summary: array}  $analysis
     * @return Collection<int, DssRestockRecommendation>
     */
    public function persist(DssRun $run, array $analysis): Collection
    {
        $saved = collect();

        foreach ($analysis['items'] as $item) {
            $saved->push(DssRestockRecommendation::updateOrCreate(
                [
                    'dss_run_id' => $run->id,
                    'product_id' => $item['product']->id,
                ],
                [
                    'supplier_id' => $item['supplier']?->id,
                    'current_stock' => $item['current_stock'],
                    'avg_daily_sales' => $item['avg_daily_sales'],
                    'lead_time_days' => $item['lead_time_days'],
                    'safety_stock' => $item['safety_stock'],
                    'reorder_point' => $item['reorder_point'],
                    'recommended_qty' => $item['recommended_qty'],
                    'estimated_cost' => $item['estimated_cost'],
                    'status' => 'draft',
                    'catatan' => 'Rata-rata penjualan '.$analysis['days'].' hari terakhir: '
                        .$item['quantity_sold'].' pcs.',
                ]
            ));
        }

        return $saved;
    }

    /**
     * Membuat pengajuan restock dari satu rekomendasi DSS.
     *
     * @param  int  $employeeId  Id employee pengaju.
     */
    public function toRestockRequest(
        DssRestockRecommendation $recommendation,
        int $employeeId,
        ?string $catatan = null
    ): RestockRequest {
        return RestockRequest::create([
            'product_id' => $recommendation->product_id,
            'employee_id' => $employeeId,
            'jumlah_restock' => max(1, (int) $recommendation->recommended_qty),
            'status' => 'pending',
            'catatan' => $catatan ?? 'Diajukan dari rekomendasi DSS (reorder point: '
                .$recommendation->reorder_point.', safety stock: '.$recommendation->safety_stock.').',
            'supplier_id' => $recommendation->supplier_id,
            'dss_run_id' => $recommendation->dss_run_id,
        ]);
    }

    /**
     * Total pcs terjual per produk pada periode analisis (hanya transaksi selesai).
     *
     * @return array<int, int>
     */
    private function salesPerProduct(int $days): array
    {
        $from = now()->subDays($days)->startOfDay();

        return DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.status', 'completed')
            ->where('transactions.date', '>=', $from)
            ->groupBy('transaction_details.product_id')
            ->select('transaction_details.product_id')
            ->selectRaw('SUM(transaction_details.quantity) AS qty')
            ->pluck('qty', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    /**
     * Memilih penawaran pemasok terbaik untuk sebuah produk.
     *
     * Prioritas: 1) pemasok dengan peringkat VIKOR terbaik di antara pemasok
     * produk tersebut (pemasok yang tidak punya peringkat ditempatkan terakhir),
     * 2) pemasok pilihan (is_preferred), 3) penawaran pertama.
     *
     * @param  array<int, int>|null  $supplierRanking  [id_pemasok] = peringkat VIKOR (1 = terbaik)
     * @return array{supplier: ?Supplier, offer: ?ProductSupplier, price: ?float, lead_time_days: ?int, min_order_qty: ?int}
     */
    private function pickSupplierOffer(Product $product, ?array $supplierRanking): array
    {
        $offers = $product->productSuppliers->filter(
            fn ($offer) => $offer->supplier !== null
        )->values();

        if ($offers->isEmpty()) {
            return [
                'supplier' => null,
                'offer' => null,
                'price' => null,
                'lead_time_days' => null,
                'min_order_qty' => null,
            ];
        }

        $chosen = null;

        if ($supplierRanking) {
            $chosen = $offers->sortBy(
                fn ($offer) => $supplierRanking[$offer->supplier_id] ?? PHP_INT_MAX
            )->first();
        }

        if ($chosen === null) {
            $chosen = $offers->firstWhere('is_preferred', true) ?? $offers->first();
        }

        $supplier = $chosen->supplier;

        return [
            'supplier' => $supplier,
            'offer' => $chosen,
            'price' => (float) $chosen->price,
            'lead_time_days' => (int) ($chosen->lead_time_days ?: ($supplier->lead_time_days ?: self::DEFAULT_LEAD_TIME_DAYS)),
            'min_order_qty' => (int) ($chosen->min_order_qty ?: ($supplier->min_order_qty ?: 1)),
        ];
    }

    /**
     * Ringkasan hasil analisis.
     *
     * @param  array<int, array>  $items
     * @return array{products: int, need_restock: int, total_qty: int, total_cost: float}
     */
    private function summarize(array $items): array
    {
        $needRestock = array_filter($items, fn ($item) => $item['needs_restock']);

        return [
            'products' => count($items),
            'need_restock' => count($needRestock),
            'total_qty' => array_sum(array_column($needRestock, 'recommended_qty')),
            'total_cost' => round(array_sum(array_column($needRestock, 'estimated_cost')), 2),
        ];
    }
}
