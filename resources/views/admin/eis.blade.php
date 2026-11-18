@extends('layouts.app', [
    'title' => 'EIS Kinerja Staf',
    'sidebar' => true,
    'activeRoute' => 'eis',
    'dashboardRoute' => 'admin.dashboard',
])

@section('content')
<div id="eisDashboard">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1"><i class="bi bi-person-badge me-2"></i>EIS Kinerja Staf</h3>
            <p class="text-muted mb-0 small">Integrasi TPS, OAS, dan DSS. Penjualan hanya transaksi <strong>completed</strong>.</p>
        </div>
        <form method="GET" action="{{ route('admin.eis') }}" class="d-flex gap-2 align-items-center">
            <label for="eisPeriod" class="small text-muted mb-0">Periode</label>
            <select name="period" id="eisPeriod" class="form-select form-select-sm" style="width: auto">
                @foreach($periods as $option)
                    <option value="{{ $option }}" @selected($option === $period)>{{ $option }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary">Terapkan</button>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <x-ui.stat-card label="Staf Aktif" :value="$overview['active_staff']" icon="bi bi-people" tone="primary" />
        <x-ui.stat-card label="Rata-rata Capaian Target" :value="number_format($overview['avg_target_achievement'], 2).'%'" icon="bi bi-bullseye" tone="success" />
        <x-ui.stat-card label="Staf Terbaik" :value="$overview['best_staff']['name'] ?? '-'" :note="($overview['best_staff']['score'] ?? null) !== null ? 'Skor Q '.number_format((float) $overview['best_staff']['score'], 4) : null" icon="bi bi-trophy" tone="warning" />
        <x-ui.stat-card label="Exception Terbuka" :value="$overview['open_exceptions']" icon="bi bi-exclamation-triangle" tone="danger" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <x-ui.card title="Tren Penjualan per Staf" icon="bi bi-graph-up">
                <div class="small text-muted mb-2">Omzet harian transaksi completed pada periode {{ $period }}.</div>
                <canvas id="eisTrend" height="110"></canvas>
            </x-ui.card>
        </div>
        <div class="col-12 col-lg-4">
            <x-ui.card title="Komposisi Aktivitas" icon="bi bi-pie-chart">
                <canvas id="eisActivity" height="160"></canvas>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <x-ui.card title="Ranking Kinerja (VIKOR)" icon="bi bi-list-ol" flush>
                <x-ui.table label="Ranking kinerja staf">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th><th>Staf</th><th>Profil</th><th>Omzet</th><th>Trx</th><th>Approval</th><th>Respons</th><th>Capaian</th><th>Q</th><th>Status</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ranking as $row)
                                <tr>
                                    <td><strong>{{ $row->rank ?? '-' }}</strong></td>
                                    <td>{{ $row->employee?->name ?? '-' }}</td>
                                    <td>{{ $row->employee?->position ?? '-' }}</td>
                                    <td>Rp {{ number_format((float) $row->total_sales, 0, ',', '.') }}</td>
                                    <td>{{ $row->transactions_count }}</td>
                                    <td>{{ $row->restock_submitted > 0 ? number_format($row->restock_approved / $row->restock_submitted * 100, 1) : '0,0' }}%</td>
                                    <td>{{ number_format((float) $row->avg_response_hours, 1) }} jam</td>
                                    <td>{{ number_format((float) $row->target_achievement, 1) }}%</td>
                                    <td>{{ $row->performance_score !== null ? number_format((float) $row->performance_score, 4) : '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $row->status === 'green' ? 'success' : ($row->status === 'yellow' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($row->status) }}
                                        </span>
                                    </td>
                                    <td><a href="{{ route('admin.eis.detail', $row->employee_id) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="11"><x-ui.empty-state title="Belum ada snapshot" description="Jalankan php artisan eis:snapshot atau buka halaman ini setelah data demo tersedia." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-ui.table>
            </x-ui.card>
        </div>
        <div class="col-12 col-xl-4">
            <x-ui.card title="Exception Terbuka" icon="bi bi-exclamation-diamond" flush>
                <x-ui.table label="Daftar exception EIS">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Staf</th><th>Jenis</th><th>Pesan</th><th></th></tr></thead>
                        <tbody>
                            @forelse($exceptions->whereNull('resolved_at') as $item)
                                <tr>
                                    <td>{{ $item->employee?->name ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $item->severity === 'danger' ? 'danger' : ($item->severity === 'warning' ? 'warning' : 'info') }}">{{ $item->type }}</span></td>
                                    <td class="small">{{ $item->message }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.eis.exception.resolve', $item) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary">Selesai</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-ui.empty-state icon="bi bi-check2-circle" title="Tidak ada exception" description="Semua indikator dalam batas normal." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-ui.table>
            </x-ui.card>
        </div>
    </div>

    <x-ui.card title="Bobot Kriteria AHP" icon="bi bi-sliders" :footer="'Consistency Ratio (CR): '.number_format((float) $criteria['ahp']['cr'], 4).' — '.($criteria['ahp']['cr'] < 0.1 ? 'konsisten' : 'perlu diperbaiki')">
        <form method="POST" action="{{ route('admin.eis.weights.store') }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-3">
                    <thead><tr><th>Kriteria</th>@foreach($criteria['criteria'] as $column)<th>{{ $column->code }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($criteria['matrix'] as $rowIndex => $row)
                            <tr>
                                <th>{{ $criteria['criteria'][$rowIndex]->code ?? $rowIndex }}</th>
                                @foreach($criteria['criteria'] as $columnIndex => $column)
                                    <td><input type="number" step="0.0001" min="0.001" name="matrix[{{ $rowIndex }}][{{ $columnIndex }}]" value="{{ old('matrix.'.$rowIndex.'.'.$columnIndex, $row[$columnIndex] ?? 1) }}" class="form-control form-control-sm"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button class="btn btn-sm btn-primary">Simpan Bobot</button>
        </form>
    </x-ui.card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
  const trend = @json($trend);
  const activity = @json($activityMix);
  if (typeof Chart === 'undefined') return;
  const token = (name, fallback) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
  const palette = [
    token('--lm-chart-1', '#2563eb'), token('--lm-chart-2', '#16a34a'),
    token('--lm-chart-3', '#f59e0b'), token('--lm-chart-4', '#ef4444'),
    token('--lm-chart-5', '#8b5cf6'),
  ];
  Chart.defaults.color = token('--lm-muted', '#64748b');
  Chart.defaults.borderColor = token('--lm-border', '#e2e8f0');
  const trendCanvas = document.getElementById('eisTrend');
  if (trendCanvas) {
    new Chart(trendCanvas, {
      type: 'line',
      data: {
        labels: trend.labels,
        datasets: (trend.datasets || []).map((set, index) => ({
          label: set.name,
          data: set.data,
          borderColor: palette[index % palette.length],
          backgroundColor: palette[index % palette.length],
          fill: false,
          tension: 0.25,
          borderWidth: 2,
        })),
      },
      options: { responsive: true, interaction: { mode: 'index', intersect: false } }
    });
  }
  const activityCanvas = document.getElementById('eisActivity');
  if (activityCanvas) {
    new Chart(activityCanvas, {
      type: 'doughnut',
      data: {
        labels: activity.labels,
        datasets: [{ data: activity.data, backgroundColor: palette, borderWidth: 1 }],
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
  }
})();
</script>
@endpush
