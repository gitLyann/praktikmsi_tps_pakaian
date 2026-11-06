<?php

namespace App\Services;

use App\Models\Chatbot;
use App\Models\DssRun;
use App\Models\EsKpiTarget;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EsService
{
    public function __construct(
        private readonly RestockRecommendationService $restockRecommendationService,
    ) {}

    /**
     * @param array{from:Carbon,to:Carbon,previousFrom:Carbon,previousTo:Carbon,days:int,label:string} $range
     */
    public function kpiSummary(array $range): array
    {
        $current = $this->completedRevenueAndCount($range['from'], $range['to']);
        $previous = $this->completedRevenueAndCount($range['previousFrom'], $range['previousTo']);
        $change = $previous['revenue'] > 0
            ? round((($current['revenue'] - $previous['revenue']) / $previous['revenue']) * 100, 2)
            : ($current['revenue'] > 0 ? 100.0 : 0.0);

        $stockVs = $this->stockVsReorder();
        $critical = $stockVs['items']->where('needs_restock', true)->count();
        $pending = RestockRequest::query()->where('status', 'pending')->count();
        $top = $this->topProducts($range, 1)['datasets'][0]['data'][0] ?? null;
        $topLabel = $this->topProducts($range, 1)['labels'][0] ?? '-';
        $bestSupplier = $this->bestSupplierFromLatestRun();

        return [
            'revenue' => ['value' => $current['revenue'], 'change_percent' => $change, 'previous' => $previous['revenue'], 'direction' => $change >= 0 ? 'up' : 'down'],
            'transactions' => ['value' => $current['count']],
            'top_product' => ['name' => $topLabel, 'sold' => $top ?? 0],
            'critical_stock' => ['value' => $critical],
            'pending_restock' => ['value' => $pending],
            'best_supplier' => $bestSupplier,
            'period' => ['label' => $range['label'], 'from' => $range['from']->toDateString(), 'to' => $range['to']->toDateString()],
        ];
    }

    public function revenueTrend(array $range): array
    {
        $daily = $range['days'] <= (int) config('es.daily_trend_max_days', 90);
        $rows = $this->completedDailyTotals($range['from'], $range['to']);
        $byDay = [];
        foreach ($rows as $row) {
            $byDay[Carbon::parse($row->day)->toDateString()] = (float) $row->revenue;
        }

        if ($daily) {
            $labels = []; $data = [];
            $cursor = $range['from']->copy()->startOfDay();
            $end = $range['to']->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $labels[] = $cursor->format('d/m');
                $data[] = round($byDay[$key] ?? 0, 2);
                $cursor->addDay();
            }
            return $this->chart($labels, [['label' => 'Omzet', 'data' => $data]], ['mode' => 'daily']);
        }

        // Monthly (up to 12 months) grouped in PHP - driver safe.
        $months = [];
        foreach ($rows as $row) {
            $m = Carbon::parse($row->day)->format('Y-m');
            $months[$m] = ($months[$m] ?? 0) + (float) $row->revenue;
        }
        ksort($months);
        $months = array_slice($months, -12, 12, true);
        $labels = array_map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M Y'), array_keys($months));
        return $this->chart(array_values($labels), [['label' => 'Omzet', 'data' => array_map(fn ($v) => round($v, 2), array_values($months))]], ['mode' => 'monthly']);
    }

    public function salesByCategory(array $range): array
    {
        $rows = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.date', [$range['from'], $range['to']])
            ->groupBy('products.category_id', 'categories.name')
            ->selectRaw('COALESCE(categories.name, ?) as label, SUM(transaction_details.subtotal) as total', ['Tanpa Kategori'])
            ->orderByDesc('total')->get();
        return $this->chart($rows->pluck('label')->all(), [['label' => 'Penjualan', 'data' => $rows->map(fn ($r) => round((float) $r->total, 2))->all()]]);
    }

    public function topProducts(array $range, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('es.top_products_limit', 5);
        $rows = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.date', [$range['from'], $range['to']])
            ->groupBy('transaction_details.product_id', 'products.name')
            ->selectRaw('products.name as label, SUM(transaction_details.quantity) as qty')
            ->orderByDesc('qty')->limit($limit)->get();
        return $this->chart($rows->pluck('label')->all(), [['label' => 'Qty Terjual', 'data' => $rows->pluck('qty')->map(fn ($v) => (int) $v)->all()]]);
    }

    public function paymentMethods(array $range): array
    {
        $rows = DB::table('transactions')
            ->where('status', 'completed')
            ->whereBetween('date', [$range['from'], $range['to']])
            ->groupBy('payment_method')
            ->selectRaw('payment_method, COUNT(*) as total, SUM(total) as revenue')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $label = $this->paymentLabel($row->payment_method);
            if (! isset($grouped[$label])) {
                $grouped[$label] = ['count' => 0, 'revenue' => 0.0];
            }
            $grouped[$label]['count'] += (int) $row->total;
            $grouped[$label]['revenue'] += (float) $row->revenue;
        }
        uasort($grouped, fn ($a, $b) => $b['count'] <=> $a['count']);
        $labels = array_keys($grouped);
        $data = array_map(fn ($l) => $grouped[$l]['count'], $labels);
        $revenue = array_map(fn ($l) => round($grouped[$l]['revenue'], 2), $labels);
        return $this->chart($labels, [['label' => 'Transaksi', 'data' => $data]], ['revenue' => $revenue]);
    }

    public function orderStatus(array $range): array
    {
        $rows = DB::table('transactions')
            ->whereBetween('date', [$range['from'], $range['to']])
            ->groupBy('status')
            ->selectRaw('status as label, COUNT(*) as total')
            ->orderBy('status')->get();
        return $this->chart($rows->pluck('label')->all(), [['label' => 'Jumlah', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]]);
    }

    public function stockVsReorder(): array
    {
        $analysis = $this->restockRecommendationService->analyze();
        $items = $analysis['items']->map(fn (array $item) => [
            'product' => $item['product']->name,
            'stock' => $item['current_stock'],
            'reorder_point' => $item['reorder_point'],
            'needs_restock' => $item['needs_restock'],
        ])->values();
        $need = $items->where('needs_restock', true)->values()->take(15);
        return [
            'items' => $items,
            'chart' => $this->chart($need->pluck('product')->all(), [
                ['label' => 'Stok', 'data' => $need->pluck('stock')->all()],
                ['label' => 'Reorder Point', 'data' => $need->pluck('reorder_point')->all()],
            ], ['total_products' => $items->count()]),
        ];
    }

    public function supplierRanking(): array
    {
        $run = $this->latestSupplierRun();
        $scores = $this->extractVikorScores($run);
        if (! $scores) {
            return $this->chart([], [['label' => 'Skor Q', 'data' => []]], ['empty' => true, 'message' => 'Belum ada analisis DSS.']);
        }
        return $this->chart(array_column($scores, 'label'), [['label' => 'Skor Q (VIKOR)', 'data' => array_column($scores, 'q')]], ['run_id' => $run?->id, 'created_at' => $run?->created_at?->toDateTimeString(), 'rank' => array_column($scores, 'rank')]);
    }

    public function alerts(array $range): array
    {
        $alerts = [];
        $summary = $this->kpiSummary($range);
        $drop = abs(min(0, $summary['revenue']['change_percent']));
        $threshold = (float) config('es.sales_drop_percent', 20);
        if ($summary['revenue']['change_percent'] < 0 && $drop >= $threshold) {
            $alerts[] = ['level' => 'danger', 'title' => 'Penjualan turun', 'message' => "Omzet turun {$drop}% dibanding periode sebelumnya.", 'source' => 'TPS'];
        }
        $criticalThreshold = (int) config('es.critical_stock_threshold', 5);
        $criticalProducts = Product::query()->where('stock', '<=', $criticalThreshold)->orderBy('stock')->limit(5)->pluck('name')->all();
        if ($criticalProducts) {
            $alerts[] = ['level' => 'warning', 'title' => 'Stok kritis', 'message' => 'Produk perlu perhatian: '.implode(', ', $criticalProducts).'.', 'source' => 'OAS'];
        }
        $pendingDays = (int) config('es.pending_restock_days', 2);
        $cutoff = now()->subDays($pendingDays);
        $stale = RestockRequest::query()->where('status', 'pending')->where('created_at', '<=', $cutoff)->count();
        if ($stale > 0) {
            $alerts[] = ['level' => 'warning', 'title' => 'Restock pending lama', 'message' => "{$stale} pengajuan restock pending lebih dari {$pendingDays} hari.", 'source' => 'OAS'];
        }
        $rankChange = $this->supplierRankChangeAlert();
        if ($rankChange) {
            $alerts[] = $rankChange;
        }
        return ['items' => $alerts, 'count' => count($alerts)];
    }

    public function integration(array $range): array
    {
        $tps = $this->completedRevenueAndCount($range['from'], $range['to']);
        $pending = RestockRequest::query()->where('status', 'pending')->count();
        $avgApproval = $this->averageApprovalHours();
        $chatCount = Chatbot::query()->count();
        $topics = Chatbot::query()->latest()->limit(5)->get()->map(function ($c) {
            $q = (string) $c->queries;
            return mb_strlen($q) > 60 ? mb_substr($q, 0, 57).'...' : $q;
        })->values()->all();
        $statusCounts = DB::table('transactions')->whereBetween('date', [$range['from'], $range['to']])->groupBy('status')->selectRaw('status, COUNT(*) as total')->pluck('total', 'status')->all();
        $activeProducts = Product::query()->count();
        $best = $this->bestSupplierFromLatestRun();
        $needRestock = $this->stockVsReorder()['items']->where('needs_restock', true)->count();
        $latestRun = $this->latestSupplierRun();

        return [
            'flow' => ['TPS', 'OAS', 'KMS', 'MIS', 'DSS', 'ES'],
            'systems' => [
                ['key' => 'tps', 'name' => 'TPS', 'desc' => 'Transaction Processing', 'metrics' => [['label' => 'Transaksi (completed)', 'value' => $tps['count']], ['label' => 'Omzet', 'value' => $tps['revenue'], 'format' => 'currency']], 'link' => route('admin.dashboard').'#analisis-mis'],
                ['key' => 'oas', 'name' => 'OAS', 'desc' => 'Office Automation', 'metrics' => [['label' => 'Restock pending', 'value' => $pending], ['label' => 'Rata-rata approval', 'value' => $avgApproval, 'format' => 'hours']], 'link' => route('admin.dashboard').'#persetujuan-restock'],
                ['key' => 'kms', 'name' => 'KMS', 'desc' => 'Knowledge Management', 'metrics' => [['label' => 'Data percakapan/FAQ', 'value' => $chatCount], ['label' => 'Topik terbanyak', 'value' => $topics ?: ['-'], 'format' => 'list']], 'link' => route('admin.dashboard')],
                ['key' => 'mis', 'name' => 'MIS', 'desc' => 'Management Information', 'metrics' => [['label' => 'Status transaksi', 'value' => $statusCounts, 'format' => 'map'], ['label' => 'Produk aktif', 'value' => $activeProducts]], 'link' => route('admin.dashboard').'#analisis-mis'],
                ['key' => 'dss', 'name' => 'DSS', 'desc' => 'Decision Support', 'metrics' => [['label' => 'Supplier terbaik', 'value' => $best['name'] ?? '-'], ['label' => 'Produk perlu restock', 'value' => $needRestock], ['label' => 'Analisis terakhir', 'value' => $latestRun?->created_at?->format('d/m/Y H:i') ?? '-']], 'link' => route('admin.dss')],
            ],
        ];
    }

    public function revenueTarget(string $period): array
    {
        $target = EsKpiTarget::query()->where('period', $period)->first();
        $from = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $actual = $this->completedRevenueAndCount($from, min($to, now()->endOfDay()))['revenue'];
        $goal = (float) ($target->revenue_target ?? 0);
        $percent = $goal > 0 ? round(($actual / $goal) * 100, 2) : 0.0;
        return [
            'period' => $period,
            'target' => $goal,
            'actual' => $actual,
            'percent' => min(100, $percent),
            'percent_raw' => $percent,
            'remaining' => max(0, $goal - $actual),
            'has_target' => $target !== null,
        ];
    }

    private function completedRevenueAndCount(Carbon $from, Carbon $to): array
    {
        $row = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(total),0) as revenue, COUNT(*) as cnt')
            ->first();
        return ['revenue' => round((float) ($row->revenue ?? 0), 2), 'count' => (int) ($row->cnt ?? 0)];
    }

    private function completedDailyTotals(Carbon $from, Carbon $to): Collection
    {
        // DATE(date) is supported by SQLite and MySQL; avoids driver-specific format functions.
        return DB::table('transactions')
            ->where('status', 'completed')
            ->whereBetween('date', [$from, $to])
            ->groupBy(DB::raw('DATE(date)'))
            ->selectRaw('DATE(date) as day, SUM(total) as revenue')
            ->orderBy('day')->get();
    }

    private function paymentLabel(?string $code): string
    {
        if (! $code) {
            return 'Lainnya';
        }
        // Group by code prefix first (robust even if payment_methods not seeded in tests).
        $c = strtolower($code);
        if (str_contains($c, 'emoney') || str_contains($c, 'ewallet') || str_contains($c, 'gopay') || str_contains($c, 'ovo') || str_contains($c, 'dana') || str_contains($c, 'shopee') || $code === 'E-Wallet') {
            return 'E-Wallet';
        }
        if (str_contains($c, 'atm') || str_contains($c, 'virtual')) {
            return 'Virtual Account';
        }
        if (str_contains($c, 'direct')) {
            return 'Direct Debit';
        }
        if (str_contains($c, 'gerai') || str_contains($c, 'alfamart') || str_contains($c, 'indomaret')) {
            return 'Gerai';
        }
        if (in_array($c, ['cash', 'cod', 'tunai'], true) || str_contains($c, 'tempat')) {
            return 'Tunai/COD';
        }
        if ($c === 'card' || str_contains($c, 'kartu')) {
            return 'Kartu';
        }

        $label = PaymentMethod::labelFor($code);
        $map = [
            'M-Banking' => 'M-Banking', 'Transfer Bank' => 'Transfer Bank', 'Online' => 'Online',
            'Bayar di Tempat (Tunai)' => 'Tunai/COD', 'Cash' => 'Tunai/COD',
        ];
        return $map[$label] ?? $label;
    }

    private function latestSupplierRun(): ?DssRun
    {
        return DssRun::query()->where('type', 'vikor')->where('status', 'completed')->latest()->first()
            ?? DssRun::query()->latest()->first();
    }

    /** @return array<int,array{label:string,q:float,rank:int}> */
    private function extractVikorScores(?DssRun $run): array
    {
        $payload = $run?->payload;
        $rows = data_get($payload, 'vikor.scores', []);
        if (! is_array($rows) || $rows === []) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'label' => (string) ($row['supplier_name'] ?? ('Supplier #'.($row['supplier_id'] ?? '?'))),
                'q' => round((float) ($row['Q'] ?? $row['q'] ?? 0), 6),
                'rank' => (int) ($row['rank'] ?? 0),
            ];
        }
        usort($out, fn ($a, $b) => $a['rank'] <=> $b['rank']);
        return $out;
    }

    private function bestSupplierFromLatestRun(): ?array
    {
        $run = $this->latestSupplierRun();
        $scores = $this->extractVikorScores($run);
        $best = $scores[0] ?? null;
        return $best ? ['name' => $best['label'], 'q' => $best['q'], 'run_id' => $run?->id] : null;
    }

    private function supplierRankChangeAlert(): ?array
    {
        $runs = DssRun::query()->where('status', 'completed')->latest()->limit(2)->get();
        if ($runs->count() < 2) {
            return null;
        }
        $a = $this->extractVikorScores($runs[0])[0]['label'] ?? null;
        $b = $this->extractVikorScores($runs[1])[0]['label'] ?? null;
        if ($a && $b && $a !== $b) {
            return ['level' => 'info', 'title' => 'Peringkat supplier berubah', 'message' => "Supplier terbaik berubah dari {$b} ke {$a} (dua analisis DSS terakhir).", 'source' => 'DSS'];
        }
        return null;
    }

    private function averageApprovalHours(): ?float
    {
        $rows = RestockRequest::query()
            ->whereIn('status', ['approved', 'rejected'])
            ->whereColumn('updated_at', '>', 'created_at')
            ->get(['created_at', 'updated_at']);
        if ($rows->isEmpty()) {
            return null;
        }
        $hours = $rows->avg(fn ($r) => $r->created_at->diffInMinutes($r->updated_at) / 60);
        return round((float) $hours, 2);
    }

    private function chart(array $labels, array $datasets, array $meta = []): array
    {
        return ['labels' => array_values($labels), 'datasets' => $datasets, 'meta' => $meta];
    }
}
