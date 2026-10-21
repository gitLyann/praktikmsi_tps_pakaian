<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rekomendasi produk untuk pelanggan.
 *
 * Skor sederhana dan bisa dijelaskan ke pengguna:
 * - +3 per kategori yang sering dibeli pelanggan (dibatasi supaya tidak dominan).
 * - +2 bila produk termasuk terlaris.
 * - +1 bila stok masih tersedia.
 * - -2 bila produk sudah pernah dibeli (agar pelanggan melihat produk baru).
 */
class ProductRecommendationService
{
    /** Periode perhitungan popularitas (hari). */
    public const POPULARITY_DAYS = 90;

    /** Batas jumlah kategori yang dipakai sebagai bobot. */
    private const MAX_CATEGORY_WEIGHT = 3;

    /**
     * Rekomendasi untuk pelanggan yang sudah login.
     *
     * @return Collection<int, array{product: Product, score: float, reasons: array<int, string>}>
     */
    public function recommendFor(User $user, int $limit = 4): Collection
    {
        $limit = max(1, $limit);

        $purchasedProductIds = $this->purchasedProductIds($user->id);
        $categoryWeights = $this->categoryWeights($user->id);
        $popularity = $this->popularity();

        $candidates = Product::query()
            ->with('category')
            ->where('stock', '>', 0)
            ->get();

        $scored = [];

        foreach ($candidates as $product) {
            $score = 0.0;
            $reasons = [];

            $weight = (int) ($categoryWeights[$product->category_id] ?? 0);
            if ($weight > 0) {
                $score += 3 * min($weight, self::MAX_CATEGORY_WEIGHT);
                $reasons[] = 'Sesuai kategori favorit Anda';
            }

            $sold = (int) ($popularity[$product->id] ?? 0);
            if ($sold > 0) {
                $score += min($sold / 5, 2);
                if ($sold >= 5) {
                    $reasons[] = 'Produk terlaris';
                }
            }

            $score += 1;
            $reasons[] = 'Stok tersedia';

            if ($purchasedProductIds !== [] && in_array($product->id, $purchasedProductIds, true)) {
                $score -= 2;
            }

            $scored[] = [
                'product' => $product,
                'score' => round($score, 4),
                'reasons' => $reasons,
            ];
        }

        return $this->sortAndLimit($scored, $limit, $purchasedProductIds);
    }

    /**
     * Rekomendasi untuk pengunjung tanpa login (hanya produk terlaris & tersedia).
     *
     * @return Collection<int, array{product: Product, score: float, reasons: array<int, string>}>
     */
    public function recommendForGuest(int $limit = 4): Collection
    {
        $limit = max(1, $limit);
        $popularity = $this->popularity();

        $candidates = Product::query()->with('category')->where('stock', '>', 0)->get();

        $scored = [];
        foreach ($candidates as $product) {
            $sold = (int) ($popularity[$product->id] ?? 0);
            $reasons = ['Stok tersedia'];

            $score = 1.0;
            if ($sold > 0) {
                $score += min($sold / 5, 2);
                if ($sold >= 5) {
                    $reasons[] = 'Produk terlaris';
                }
            }

            $scored[] = [
                'product' => $product,
                'score' => round($score, 4),
                'reasons' => $reasons,
            ];
        }

        return $this->sortAndLimit($scored, $limit, []);
    }

    /**
     * Mengurutkan skor dan mengambil jumlah teratas.
     *
     * @param  array<int, array{product: Product, score: float, reasons: array<int, string>}>  $scored
     * @param  array<int, int>  $purchasedProductIds
     * @return Collection<int, array{product: Product, score: float, reasons: array<int, string>}>
     */
    private function sortAndLimit(array $scored, int $limit, array $purchasedProductIds): Collection
    {
        usort($scored, function ($a, $b) use ($purchasedProductIds) {
            $diff = $b['score'] <=> $a['score'];

            if ($diff !== 0) {
                return $diff;
            }

            // Preferensi: produk yang belum pernah dibeli.
            $aBought = in_array($a['product']->id, $purchasedProductIds, true) ? 1 : 0;
            $bBought = in_array($b['product']->id, $purchasedProductIds, true) ? 1 : 0;

            return $aBought <=> $bBought;
        });

        return collect(array_slice($scored, 0, $limit))->values();
    }

    /**
     * Id produk yang pernah dibeli pelanggan.
     *
     * @return array<int, int>
     */
    private function purchasedProductIds(int $userId): array
    {
        return DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.customer_id', $userId)
            ->where('transactions.status', 'completed')
            ->distinct()
            ->pluck('transaction_details.product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Frekuensi pembelian per kategori.
     *
     * @return array<int, int>
     */
    private function categoryWeights(int $userId): array
    {
        return DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->where('transactions.customer_id', $userId)
            ->where('transactions.status', 'completed')
            ->groupBy('products.category_id')
            ->select('products.category_id')
            ->selectRaw('COUNT(*) AS total')
            ->pluck('total', 'category_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * Total pcs terjual per produk dalam periode popularitas.
     *
     * @return array<int, int>
     */
    private function popularity(): array
    {
        $from = now()->subDays(self::POPULARITY_DAYS)->startOfDay();

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
}
