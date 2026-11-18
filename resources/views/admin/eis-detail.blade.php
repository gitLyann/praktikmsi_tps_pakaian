@extends('layouts.app', [
    'title' => 'Detail Kinerja Staf',
    'sidebar' => true,
    'activeRoute' => 'eis',
    'dashboardRoute' => 'admin.dashboard',
])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-1"><i class="bi bi-person-lines-fill me-2"></i>{{ $employee->name }}</h3>
        <p class="text-muted mb-0 small">Periode {{ $period }} · {{ $employee->position ?? 'Staf' }}</p>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.eis.detail', $employee->id) }}">
            <select name="period" class="form-select form-select-sm" style="width: auto" onchange="this.form.submit()">
                @foreach($periods as $option)
                    <option value="{{ $option }}" @selected($option === $period)>{{ $option }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('admin.eis', ['period' => $period]) }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <x-ui.stat-card label="Omzet" :value="'Rp '.number_format((float) ($snapshot?->total_sales ?? 0), 0, ',', '.')" icon="bi bi-cash-stack" tone="primary" />
    <x-ui.stat-card label="Transaksi" :value="$snapshot?->transactions_count ?? 0" icon="bi bi-receipt" tone="success" />
    <x-ui.stat-card label="Capaian Target" :value="number_format((float) ($snapshot?->target_achievement ?? 0), 2).'%'" icon="bi bi-bullseye" tone="warning" />
    <x-ui.stat-card label="Skor Q / Ranking" :value="($snapshot?->performance_score !== null ? number_format((float) $snapshot->performance_score, 4) : '-').' / '.($snapshot?->rank ?? '-')" icon="bi bi-trophy" tone="danger" />
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <x-ui.card title="Tren Penjualan" icon="bi bi-graph-up">
            <canvas id="eisDetailTrend" height="110"></canvas>
        </x-ui.card>
    </div>
    <div class="col-12 col-lg-4">
        <x-ui.card title="Target Periode" icon="bi bi-bullseye">
            <form method="POST" action="{{ route('admin.eis.target.store') }}">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="mb-2">
                    <label class="form-label small">Target Omzet (Rp)</label>
                    <input type="number" name="sales_target" class="form-control form-control-sm" min="0" step="0.01" value="{{ old('sales_target', $target?->sales_target ?? 0) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Target Transaksi</label>
                    <input type="number" name="transaction_target" class="form-control form-control-sm" min="0" value="{{ old('transaction_target', $target?->transaction_target ?? 0) }}" required>
                </div>
                <button class="btn btn-sm btn-primary">Simpan Target</button>
            </form>
        </x-ui.card>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <x-ui.card title="Transaksi Terakhir" icon="bi bi-receipt" flush>
            <x-ui.table label="Transaksi terakhir staf">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Tanggal</th><th>Total</th><th>Metode</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($transactions as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y H:i') }}</td>
                                <td>Rp {{ number_format((float) $row->total, 0, ',', '.') }}</td>
                                <td>{{ $row->payment_method ?? '-' }}</td>
                                <td><span class="badge bg-success">{{ $row->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-ui.empty-state title="Belum ada transaksi" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table>
        </x-ui.card>
    </div>
    <div class="col-12 col-xl-6">
        <x-ui.card title="Aktivitas & Restock" icon="bi bi-clock-history" flush>
            <x-ui.table label="Aktivitas staf">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Waktu</th><th>Aktivitas</th><th>Referensi</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($activity_logs as $row)
                            <tr>
                                <td>{{ $row->occurred_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ str_replace('_', ' ', $row->activity_type) }}</td>
                                <td>{{ $row->reference_type }} #{{ $row->reference_id }}</td>
                                <td>{{ $row->meta['status'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-ui.empty-state title="Belum ada aktivitas" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table>
        </x-ui.card>
    </div>
    <div class="col-12">
        <x-ui.card title="Exception" icon="bi bi-exclamation-triangle" flush>
            <x-ui.table label="Exception staf">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Jenis</th><th>Severity</th><th>Pesan</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($exceptions as $row)
                            <tr>
                                <td>{{ $row->type }}</td>
                                <td><span class="badge bg-{{ $row->severity === 'danger' ? 'danger' : ($row->severity === 'warning' ? 'warning' : 'info') }}">{{ $row->severity }}</span></td>
                                <td>{{ $row->message }}</td>
                                <td>{{ $row->resolved_at ? 'Selesai' : 'Terbuka' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-ui.empty-state icon="bi bi-check2-circle" title="Tidak ada exception" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table>
        </x-ui.card>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
  if (typeof Chart === 'undefined') return;
  const trend = @json($trend);
  const canvas = document.getElementById('eisDetailTrend');
  if (!canvas) return;
  const token = (name, fallback) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
  Chart.defaults.color = token('--lm-muted', '#64748b');
  Chart.defaults.borderColor = token('--lm-border', '#e2e8f0');
  new Chart(canvas, {
    type: 'line',
    data: {
      labels: trend.labels,
      datasets: (trend.datasets || []).map((set) => ({
        label: set.name,
        data: set.data,
        borderColor: token('--lm-chart-1', '#2563eb'),
        backgroundColor: token('--lm-chart-1', '#2563eb'),
        fill: false,
        tension: 0.25,
      })),
    },
    options: { responsive: true }
  });
})();
</script>
@endpush
