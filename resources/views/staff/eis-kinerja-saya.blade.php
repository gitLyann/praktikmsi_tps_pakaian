@extends('layouts.app', [
    'title' => 'Kinerja Saya',
    'sidebar' => true,
    'activeRoute' => 'eis',
    'dashboardRoute' => 'staff.dashboard',
])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-1"><i class="bi bi-person-lines-fill me-2"></i>Kinerja Saya</h3>
        <p class="text-muted mb-0 small">Data kinerja periode {{ $period }}. Hanya aktivitas akun Anda yang ditampilkan.</p>
    </div>
    <form method="GET" action="{{ route('staff.eis.index') }}" class="d-flex gap-2 align-items-center">
        <label for="eisStaffPeriod" class="small text-muted mb-0">Periode</label>
        <select name="period" id="eisStaffPeriod" class="form-select form-select-sm" style="width: auto">
            @foreach($periods as $option)
                <option value="{{ $option }}" @selected($option === $period)>{{ $option }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-primary">Terapkan</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <x-ui.stat-card label="Omzet Saya" :value="'Rp '.number_format((float) ($snapshot?->total_sales ?? 0), 0, ',', '.')" icon="bi bi-cash-stack" tone="primary" />
    <x-ui.stat-card label="Transaksi" :value="$snapshot?->transactions_count ?? 0" icon="bi bi-receipt" tone="success" />
    <x-ui.stat-card label="Capaian Target" :value="number_format((float) ($snapshot?->target_achievement ?? 0), 2).'%'" :note="$target ? 'Target Rp '.number_format((float) $target->sales_target, 0, ',', '.') : 'Target belum ditetapkan'" icon="bi bi-bullseye" tone="warning" />
    <x-ui.stat-card label="Peringkat" :value="$snapshot?->rank ?? '-'" :note="$snapshot?->performance_score !== null ? 'Skor Q '.number_format((float) $snapshot->performance_score, 4) : null" icon="bi bi-trophy" tone="danger" />
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <x-ui.card title="Tren Penjualan Saya" icon="bi bi-graph-up">
            <canvas id="eisStaffTrend" height="110"></canvas>
        </x-ui.card>
    </div>
    <div class="col-12 col-lg-4">
        <x-ui.card title="Aktivitas Saya" icon="bi bi-pie-chart">
            <canvas id="eisStaffActivity" height="160"></canvas>
        </x-ui.card>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-6">
        <x-ui.card title="Transaksi Terakhir" icon="bi bi-receipt" flush>
            <x-ui.table label="Transaksi saya">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($transactions as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y H:i') }}</td>
                                <td>Rp {{ number_format((float) $row->total, 0, ',', '.') }}</td>
                                <td><span class="badge bg-success">{{ $row->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-ui.empty-state title="Belum ada transaksi pada periode ini" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table>
        </x-ui.card>
    </div>
    <div class="col-12 col-xl-6">
        <x-ui.card title="Restock & Penilaian" icon="bi bi-clock-history" flush>
            <x-ui.table label="Aktivitas saya">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Waktu</th><th>Aktivitas</th><th>Detail</th></tr></thead>
                    <tbody>
                        @forelse($activity_logs as $row)
                            <tr>
                                <td>{{ $row->occurred_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ str_replace('_', ' ', $row->activity_type) }}</td>
                                <td>{{ $row->meta['status'] ?? $row->reference_type }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-ui.empty-state title="Belum ada aktivitas pada periode ini" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table>
        </x-ui.card>
    </div>
    @if($exceptions->isNotEmpty())
        <div class="col-12">
            <x-ui.card title="Catatan Perbaikan" icon="bi bi-exclamation-triangle" flush>
                <x-ui.table label="Catatan perbaikan">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Jenis</th><th>Severity</th><th>Pesan</th></tr></thead>
                        <tbody>
                            @foreach($exceptions as $row)
                                <tr>
                                    <td>{{ $row->type }}</td>
                                    <td><span class="badge bg-{{ $row->severity === 'danger' ? 'danger' : ($row->severity === 'warning' ? 'warning' : 'info') }}">{{ $row->severity }}</span></td>
                                    <td>{{ $row->message }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.table>
            </x-ui.card>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
  if (typeof Chart === 'undefined') return;
  const trend = @json($trend);
  const activity = @json($activityMix);
  const token = (name, fallback) => getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
  Chart.defaults.color = token('--lm-muted', '#64748b');
  Chart.defaults.borderColor = token('--lm-border', '#e2e8f0');
  const trendCanvas = document.getElementById('eisStaffTrend');
  if (trendCanvas) {
    new Chart(trendCanvas, {
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
  }
  const activityCanvas = document.getElementById('eisStaffActivity');
  if (activityCanvas) {
    new Chart(activityCanvas, {
      type: 'doughnut',
      data: {
        labels: activity.labels,
        datasets: [{
          data: activity.data,
          backgroundColor: [
            token('--lm-chart-1', '#2563eb'), token('--lm-chart-2', '#16a34a'),
            token('--lm-chart-3', '#f59e0b'), token('--lm-chart-4', '#ef4444'),
          ],
        }],
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
  }
})();
</script>
@endpush
