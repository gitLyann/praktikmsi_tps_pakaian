@extends('layouts.app', [
    'title' => 'Analisis DSS - AHP & VIKOR',
    'sidebar' => true,
    'activeRoute' => 'dss',
    'dashboardRoute' => 'admin.dashboard'
])

@section('content')
    @php
        $ahp = $analysis['ahp'];
        $vikor = $analysis['vikor'];
        $restock = $analysis['restock'];
    @endphp

    <div id="adminDss" data-dss>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h3 class="mb-1">Analisis DSS (AHP + VIKOR)</h3>
                <p class="text-muted mb-0">
                    Bobot kriteria dihitung dengan AHP, peringkat pemasok dengan VIKOR, dan kebutuhan
                    restock disimulasikan dari penjualan {{ $restock['days'] }} hari terakhir.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.dss.run') }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-play-circle me-1"></i>Jalankan &amp; Simpan Analisis
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="mb-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <x-ui.toast type="danger" class="mb-3">{{ session('error') }}</x-ui.toast>
        @endif

        {{-- Navigasi tab modul DSS --}}
        <x-ui.tabs
            :items="[
                ['id' => 'dss-ringkasan', 'label' => 'Ringkasan', 'icon' => 'bi bi-speedometer2', 'active' => true],
                ['id' => 'dss-bobot-ahp', 'label' => 'Bobot AHP', 'icon' => 'bi bi-diagram-3'],
                ['id' => 'dss-ranking', 'label' => 'Ranking VIKOR', 'icon' => 'bi bi-trophy'],
                ['id' => 'dss-restock', 'label' => 'Restock', 'icon' => 'bi bi-box-seam'],
                ['id' => 'dss-pemasok', 'label' => 'Pemasok', 'icon' => 'bi bi-truck'],
                ['id' => 'dss-riwayat', 'label' => 'Riwayat', 'icon' => 'bi bi-clock-history'],
            ]"
            dss
            class="mb-4"
        />

        {{-- ============================ RINGKASAN ============================ --}}
        <div data-dss-section="dss-ringkasan">
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="lm-stat-card">
                        <div class="lm-stat-icon {{ $restock['need_restock'] > 0 ? 'is-danger' : 'is-success' }}">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="lm-stat-body">
                            <div class="lm-stat-label">Produk Perlu Restock</div>
                            <div class="lm-stat-value">{{ $restock['need_restock'] }}</div>
                            <div class="lm-stat-note">
                                dari {{ $restock['products'] }} produk dipantau &middot; saran total
                                {{ $restock['total_qty'] }} pcs
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="lm-stat-card">
                        <div class="lm-stat-icon is-info">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="lm-stat-body">
                            <div class="lm-stat-label">Supplier Aktif</div>
                            <div class="lm-stat-value">{{ $activeSupplierCount }}</div>
                            <div class="lm-stat-note">
                                alternatif VIKOR: {{ count($suppliers) }} pemasok terdaftar
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="lm-stat-card">
                        <div class="lm-stat-icon is-warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="lm-stat-body">
                            <div class="lm-stat-label">Analisis Terakhir</div>
                            <div class="lm-stat-value">
                                {{ $latestRun ? '#'.$latestRun->id : '-' }}
                            </div>
                            <div class="lm-stat-note">
                                @if($latestRun)
                                    {{ $latestRun->created_at?->format('d/m/Y H:i') }}
                                    &middot; CR {{ number_format((float) $latestRun->cr, 4) }}
                                @else
                                    Belum ada analisis yang disimpan.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i>Ringkasan Bobot Kriteria (AHP)</h5>
                </div>
                <div class="card-body">
                    <div class="lm-metric-row mb-3">
                        <div class="lm-metric">
                            <div class="lm-metric-label">Jumlah Kriteria</div>
                            <div class="lm-metric-value">{{ $ahp['criteria_count'] }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Lambda Max</div>
                            <div class="lm-metric-value">{{ number_format($ahp['lambda_max'], 4) }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">CI</div>
                            <div class="lm-metric-value">{{ number_format($ahp['ci'], 4) }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">RI</div>
                            <div class="lm-metric-value">{{ number_format($ahp['ri'], 4) }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">CR</div>
                            <div class="lm-metric-value {{ $ahp['is_consistent'] ? 'is-success' : 'is-danger' }}">
                                {{ number_format($ahp['cr'], 4) }}
                            </div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Status Konsistensi</div>
                            <div class="lm-metric-value {{ $ahp['is_consistent'] ? 'is-success' : 'is-danger' }}">
                                {{ $ahp['is_consistent'] ? 'Konsisten' : 'Tidak Konsisten' }}
                            </div>
                        </div>
                    </div>

                    <div class="lm-table-wrap">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Kriteria</th>
                                    <th>Tipe</th>
                                    <th style="width: 260px;">Bobot</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ahp['weights'] as $weight)
                                    <tr>
                                        <td class="lm-score">{{ $weight['code'] }}</td>
                                        <td>{{ $weight['name'] }}</td>
                                        <td>
                                            <span class="lm-badge {{ $weight['type'] === 'benefit' ? 'lm-badge-info' : 'lm-badge-neutral' }}">
                                                {{ $weight['type'] === 'benefit' ? 'Benefit' : 'Cost' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="lm-score" style="min-width: 64px;">{{ number_format($weight['weight'], 4) }}</span>
                                                <div class="lm-score-bar flex-grow-1">
                                                    <span style="width: {{ min(100, $weight['weight'] * 100) }}%"></span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Belum ada kriteria penilaian.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================ BOBOT AHP ============================ --}}
        <div data-dss-section="dss-bobot-ahp" class="d-none">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Form Matriks Perbandingan (Skala Saaty 1-9)</h5>
                </div>
                <div class="card-body">
                    @if($matrix['criteria'] === [])
                        <x-ui.empty-state
                            icon="bi bi-list-check"
                            title="Belum ada kriteria penilaian"
                            description="Tambahkan kriteria pada tabel dss_criteria terlebih dahulu."
                        />
                    @else
                        <p class="text-muted small">
                            Isi kotak bagian atas diagonal: nilai perbandingan kriteria baris terhadap kolom
                            (1 = sama penting, 9 = jauh lebih penting). Bagian bawah diagonal otomatis
                            merupakan resiprok, diagonal selalu 1.
                        </p>

                        <form method="POST" action="{{ route('admin.dss.weights.store') }}">
                            @csrf
                            <div class="lm-table-wrap mb-3">
                                <table class="table lm-matrix mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-start">Kriteria \ Kriteria</th>
                                            @foreach($matrix['criteria'] as $column)
                                                <th>{{ $column['code'] }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($matrix['criteria'] as $rowIndex => $row)
                                            <tr>
                                                <th class="text-start" style="min-width: 180px;">
                                                    {{ $row['code'] }}
                                                    <span class="fw-normal small text-muted d-block">{{ $row['name'] }}</span>
                                                </th>
                                                @foreach($matrix['criteria'] as $columnIndex => $column)
                                                    @php
                                                        $key = $row['id'].'-'.$column['id'];
                                                        $value = $matrix['values'][$key] ?? null;
                                                    @endphp
                                                    @if($rowIndex === $columnIndex)
                                                        <td class="lm-score">1</td>
                                                    @elseif($columnIndex > $rowIndex)
                                                        <td>
                                                            <input type="number" step="1" min="1" max="9"
                                                                class="form-control form-control-sm text-center"
                                                                name="values[{{ $key }}]"
                                                                value="{{ $value !== null ? $value : '' }}"
                                                                placeholder="-">
                                                        </td>
                                                    @else
                                                        <td class="lm-score text-muted">
                                                            {{ $value !== null ? number_format(1 / max((float) $value, 0.0001), 4) : '-' }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <p class="text-muted small mb-0">
                                    Nilai perbandingan yang tersimpan: <strong>{{ $ahp['criteria_count'] }}</strong>
                                    kriteria, CR saat ini <strong>{{ number_format($ahp['cr'], 4) }}</strong>
                                    ({{ $ahp['is_consistent'] ? 'konsisten' : 'belum konsisten' }}).
                                </p>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Simpan Matriks &amp; Hitung Ulang
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-list-ol me-2"></i>Bobot &amp; Uji Konsistensi</h5>
                </div>
                <div class="card-body">
                    <div class="lm-metric-row mb-3">
                        <div class="lm-metric">
                            <div class="lm-metric-label">Lambda Max</div>
                            <div class="lm-metric-value">{{ number_format($ahp['lambda_max'], 4) }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">CI</div>
                            <div class="lm-metric-value">{{ number_format($ahp['ci'], 4) }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">RI</div>
                            <div class="lm-metric-value">{{ number_format($ahp['ri'], 4) }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">CR</div>
                            <div class="lm-metric-value {{ $ahp['is_consistent'] ? 'is-success' : 'is-danger' }}">
                                {{ number_format($ahp['cr'], 4) }}
                            </div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Status</div>
                            <div class="lm-metric-value {{ $ahp['is_consistent'] ? 'is-success' : 'is-danger' }}">
                                {{ $ahp['is_consistent'] ? 'Konsisten' : 'Tidak Konsisten' }}
                            </div>
                        </div>
                    </div>

                    <div class="lm-table-wrap">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Kriteria</th>
                                    <th>Tipe</th>
                                    <th>Bobot</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ahp['weights'] as $weight)
                                    <tr>
                                        <td class="lm-score">{{ $weight['code'] }}</td>
                                        <td>{{ $weight['name'] }}</td>
                                        <td>{{ $weight['type'] === 'benefit' ? 'Benefit' : 'Cost' }}</td>
                                        <td class="lm-score">{{ number_format($weight['weight'], 4) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Belum ada kriteria penilaian.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================ RANKING VIKOR ============================ --}}
        <div data-dss-section="dss-ranking" class="d-none">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Peringkat Pemasok (S, R, Q)</h5>
                </div>
                <div class="card-body">
                    <div class="lm-metric-row mb-4">
                        <div class="lm-metric">
                            <div class="lm-metric-label">Bobot v</div>
                            <div class="lm-metric-value">{{ number_format($vikor['v'], 2) }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Ambang DQ</div>
                            <div class="lm-metric-value">{{ number_format($vikor['threshold'], 4) }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Klasifikasi Terbaik</div>
                            <div class="lm-metric-value">{{ $vikor['best_classification'] }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Keunggulan (C1)</div>
                            <div class="lm-metric-value {{ $vikor['has_advantage'] ? 'is-success' : 'is-danger' }}">
                                {{ $vikor['has_advantage'] ? 'Diterima' : 'Belum' }}
                            </div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Stabilitas (C2)</div>
                            <div class="lm-metric-value {{ $vikor['is_stable'] ? 'is-success' : 'is-danger' }}">
                                {{ $vikor['is_stable'] ? 'Diterima' : 'Belum' }}
                            </div>
                        </div>
                    </div>

                    @if($vikor['scores'] === [])
                        <div class="lm-empty">
                            <i class="bi bi-clipboard-data"></i>
                            <div class="lm-empty-title">Belum ada skor pemasok</div>
                            <div class="lm-empty-text">
                                Staff belum mengisi penilaian pemasok. Akses halaman Penilaian Pemasok untuk mengisi skor.
                            </div>
                        </div>
                    @else
                        <div class="lm-table-wrap">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 90px;">Peringkat</th>
                                        <th>Pemasok</th>
                                        <th>S</th>
                                        <th>R</th>
                                        <th>Q</th>
                                        <th>Label Solusi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vikor['scores'] as $score)
                                        <tr>
                                            <td>
                                                <span class="lm-badge lm-badge-rank {{ $score['rank'] === 1 ? 'is-top' : '' }}">
                                                    #{{ $score['rank'] }}
                                                </span>
                                            </td>
                                            <td>{{ $score['supplier_name'] }}</td>
                                            <td class="lm-score">{{ number_format($score['S'], 4) }}</td>
                                            <td class="lm-score">{{ number_format($score['R'], 4) }}</td>
                                            <td class="lm-score">{{ number_format($score['Q'], 4) }}</td>
                                            <td>
                                                <span class="lm-badge {{ $score['is_best'] ? 'lm-badge-approved' : 'lm-badge-neutral' }}">
                                                    {{ $score['classification'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i>Data Skor Penilaian Pemasok</h5>
                </div>
                <div class="card-body">
                    @if($suppliers === [])
                        <x-ui.empty-state
                            icon="bi bi-truck"
                            title="Belum ada pemasok"
                            description="Data pemasok dipakai sebagai alternatif dalam VIKOR."
                        />
                    @else
                        <div class="lm-table-wrap">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pemasok</th>
                                        @foreach($criteria as $criterion)
                                            <th class="text-center">{{ $criterion['code'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($suppliers as $supplier)
                                        <tr>
                                            <th class="text-start">{{ $supplier['supplier_name'] }}</th>
                                            @foreach($supplier['scores'] as $score)
                                                <td class="text-center lm-score">
                                                    {{ $score['score'] !== null ? number_format($score['score'], 2) : '-' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================ RESTOCK ============================ --}}
        <div data-dss-section="dss-restock" class="d-none">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Rekomendasi Restock</h5>
                </div>
                <div class="card-body">
                    <div class="lm-metric-row mb-4">
                        <div class="lm-metric">
                            <div class="lm-metric-label">Produk Dipantau</div>
                            <div class="lm-metric-value">{{ $restock['products'] }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Perlu Restock</div>
                            <div class="lm-metric-value {{ $restock['need_restock'] > 0 ? 'is-danger' : 'is-success' }}">
                                {{ $restock['need_restock'] }}
                            </div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Total Saran Qty</div>
                            <div class="lm-metric-value">{{ $restock['total_qty'] }}</div>
                        </div>
                        <div class="lm-metric">
                            <div class="lm-metric-label">Estimasi Biaya</div>
                            <div class="lm-metric-value">Rp {{ number_format($restock['total_cost'], 0, ',', '.') }}</div>
                        </div>
                    </div>

                    @if($restock['items'] === [])
                        <div class="lm-empty">
                            <i class="bi bi-box-seam"></i>
                            <div class="lm-empty-title">Belum ada produk yang dipantau</div>
                            <div class="lm-empty-text">Rekomendasi restock butuh data produk dan penawaran pemasok.</div>
                        </div>
                    @else
                        <div class="lm-table-wrap">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th>Pemasok</th>
                                        <th>Stok</th>
                                        <th>Rata-rata/Hari</th>
                                        <th>Safety Stock</th>
                                        <th>Reorder Point</th>
                                        <th>Saran Qty</th>
                                        <th>Estimasi Biaya</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($restock['items'] as $item)
                                        <tr>
                                            <td>{{ $item['product_name'] }}</td>
                                            <td>{{ $item['supplier_name'] ?? '-' }}</td>
                                            <td class="lm-score">{{ $item['current_stock'] }}</td>
                                            <td class="lm-score">{{ number_format($item['avg_daily_sales'], 2) }}</td>
                                            <td class="lm-score">{{ $item['safety_stock'] }}</td>
                                            <td class="lm-score">{{ $item['reorder_point'] }}</td>
                                            <td class="lm-score">{{ $item['recommended_qty'] }}</td>
                                            <td class="lm-score">Rp {{ number_format($item['estimated_cost'], 0, ',', '.') }}</td>
                                            <td>
                                                @if($item['needs_restock'])
                                                    <span class="lm-badge lm-badge-rejected">Perlu Restock</span>
                                                @else
                                                    <span class="lm-badge lm-badge-approved">Aman</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('admin.dss.restock.store') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                                    <input type="hidden" name="jumlah_restock" value="{{ max(1, (int) $item['recommended_qty']) }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-send me-1"></i>Buat Pengajuan
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Pengajuan Restock dari DSS</h5>
                </div>
                <div class="card-body">
                    @if($restockRequests->isEmpty())
                        <x-ui.empty-state
                            icon="bi bi-send"
                            title="Belum ada pengajuan restock dari DSS"
                            description='Klik "Buat Pengajuan" pada tabel rekomendasi di atas.'
                        />
                    @else
                        <div class="lm-table-wrap">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Produk</th>
                                        <th>Pemasok</th>
                                        <th>Qty</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($restockRequests as $restockRequest)
                                        <tr>
                                            <td class="lm-score">{{ $restockRequest->id }}</td>
                                            <td>{{ $restockRequest->product?->name ?? '-' }}</td>
                                            <td>{{ $restockRequest->supplier?->name ?? '-' }}</td>
                                            <td class="lm-score">{{ $restockRequest->jumlah_restock }}</td>
                                            <td>
                                                <span class="lm-badge {{ $restockRequest->status === 'approved' ? 'lm-badge-approved' : ($restockRequest->status === 'rejected' ? 'lm-badge-rejected' : 'lm-badge-pending') }}">
                                                    {{ ucfirst($restockRequest->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $restockRequest->created_at?->format('d/m/Y H:i') }}</td>
                                            <td class="text-end">
                                                @if($restockRequest->status === 'pending')
                                                    <form method="POST" action="{{ route('admin.restock.update', $restockRequest->id) }}" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="bi bi-check-lg me-1"></i>Setujui
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.restock.update', $restockRequest->id) }}" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-x-lg me-1"></i>Tolak
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">Selesai</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================ PEMASOK ============================ --}}
        <div data-dss-section="dss-pemasok" class="d-none">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Pemasok Baru</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.dss.suppliers.store') }}">
                        @csrf
                        <div class="lm-form-grid mb-3">
                            <div>
                                <label class="form-label" for="supplierName">Nama Pemasok</label>
                                <input type="text" class="form-control" id="supplierName" name="name" required>
                            </div>
                            <div>
                                <label class="form-label" for="supplierCode">Kode</label>
                                <input type="text" class="form-control" id="supplierCode" name="code" placeholder="SUP-006" required>
                            </div>
                            <div>
                                <label class="form-label" for="supplierContact">Narahubung</label>
                                <input type="text" class="form-control" id="supplierContact" name="contact_name">
                            </div>
                            <div>
                                <label class="form-label" for="supplierPhone">Telepon</label>
                                <input type="text" class="form-control" id="supplierPhone" name="phone">
                            </div>
                            <div>
                                <label class="form-label" for="supplierEmail">Email</label>
                                <input type="email" class="form-control" id="supplierEmail" name="email">
                            </div>
                            <div>
                                <label class="form-label" for="supplierCity">Kota</label>
                                <input type="text" class="form-control" id="supplierCity" name="city">
                            </div>
                            <div>
                                <label class="form-label" for="supplierLeadTime">Lead Time (hari)</label>
                                <input type="number" min="1" max="60" class="form-control" id="supplierLeadTime" name="lead_time_days" value="7" required>
                            </div>
                            <div>
                                <label class="form-label" for="supplierMoq">Minimum Order</label>
                                <input type="number" min="1" class="form-control" id="supplierMoq" name="min_order_qty" value="10" required>
                            </div>
                            <div>
                                <label class="form-label" for="supplierRating">Rating (0-5)</label>
                                <input type="number" step="0.1" min="0" max="5" class="form-control" id="supplierRating" name="rating" value="4">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Pemasok
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Daftar Pemasok &amp; Relasi Produk</h5>
                </div>
                <div class="card-body">
                    @if($suppliers === [])
                        <x-ui.empty-state
                            icon="bi bi-truck"
                            title="Belum ada pemasok"
                            description="Tambahkan pemasok lewat form di atas."
                        />
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach($supplierList as $supplier)
                                <details class="border rounded p-3 bg-white">
                                    <summary class="fw-bold">
                                        {{ $supplier->name }}
                                        <span class="lm-badge lm-badge-neutral ms-2">{{ $supplier->code }}</span>
                                        @if(! $supplier->is_active)
                                            <span class="lm-badge lm-badge-rejected ms-1">Nonaktif</span>
                                        @endif
                                    </summary>

                                    <div class="lm-table-wrap my-3">
                                        <table class="table table-sm table-bordered align-middle mb-0">
                                            <tbody>
                                                <tr>
                                                    <th class="text-start" style="width: 180px;">Kontak</th>
                                                    <td>
                                                        {{ $supplier->contact_name ?: '-' }}
                                                        &middot; {{ $supplier->phone ?: '-' }}
                                                        &middot; {{ $supplier->email ?: '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="text-start">Lokasi</th>
                                                    <td>{{ $supplier->city ?: '-' }} {{ $supplier->address ? '('.$supplier->address.')' : '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-start">Lead Time / MOQ</th>
                                                    <td>{{ $supplier->lead_time_days }} hari &middot; MOQ {{ $supplier->min_order_qty }} &middot; rating {{ number_format((float) $supplier->rating, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <form method="POST" action="{{ route('admin.dss.suppliers.update', $supplier->id) }}" class="mb-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="lm-form-grid mb-2">
                                            <div>
                                                <label class="form-label">Nama</label>
                                                <input type="text" class="form-control" name="name" value="{{ $supplier->name }}" required>
                                            </div>
                                            <div>
                                                <label class="form-label">Kode</label>
                                                <input type="text" class="form-control" name="code" value="{{ $supplier->code }}" required>
                                            </div>
                                            <div>
                                                <label class="form-label">Narahubung</label>
                                                <input type="text" class="form-control" name="contact_name" value="{{ $supplier->contact_name }}">
                                            </div>
                                            <div>
                                                <label class="form-label">Telepon</label>
                                                <input type="text" class="form-control" name="phone" value="{{ $supplier->phone }}">
                                            </div>
                                            <div>
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" value="{{ $supplier->email }}">
                                            </div>
                                            <div>
                                                <label class="form-label">Kota</label>
                                                <input type="text" class="form-control" name="city" value="{{ $supplier->city }}">
                                            </div>
                                            <div>
                                                <label class="form-label">Lead Time</label>
                                                <input type="number" min="1" max="60" class="form-control" name="lead_time_days" value="{{ $supplier->lead_time_days }}" required>
                                            </div>
                                            <div>
                                                <label class="form-label">Minimum Order</label>
                                                <input type="number" min="1" class="form-control" name="min_order_qty" value="{{ $supplier->min_order_qty }}" required>
                                            </div>
                                            <div>
                                                <label class="form-label">Rating</label>
                                                <input type="number" step="0.1" min="0" max="5" class="form-control" name="rating" value="{{ $supplier->rating }}">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-save me-1"></i>Simpan Perubahan
                                        </button>
                                    </form>

                                    <div class="d-flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.dss.suppliers.toggle', $supplier->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                {{ $supplier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.dss.suppliers.destroy', $supplier->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i>Hapus
                                            </button>
                                        </form>
                                    </div>

                                    <hr>

                                    <h6 class="fw-bold mb-2"><i class="bi bi-box me-1"></i>Relasi Produk (penawaran)</h6>
                                    @if($supplier->productSuppliers->isEmpty())
                                        <p class="text-muted small mb-2">Belum ada produk yang dilayani pemasok ini.</p>
                                    @else
                                        <div class="lm-table-wrap mb-2">
                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Produk</th>
                                                        <th>Harga Beli</th>
                                                        <th>MOQ</th>
                                                        <th>Lead Time</th>
                                                        <th>Pilihan</th>
                                                        <th class="text-end">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($supplier->productSuppliers as $offer)
                                                        <tr>
                                                            <td>{{ $offer->product?->name ?? '-' }}</td>
                                                            <td class="lm-score">Rp {{ number_format((float) $offer->price, 0, ',', '.') }}</td>
                                                            <td class="lm-score">{{ $offer->min_order_qty }}</td>
                                                            <td class="lm-score">{{ $offer->lead_time_days }} hari</td>
                                                            <td>
                                                                @if($offer->is_preferred)
                                                                    <span class="lm-badge lm-badge-approved">Utama</span>
                                                                @else
                                                                    <span class="lm-badge lm-badge-neutral">Cadangan</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-end">
                                                                <form method="POST" action="{{ route('admin.dss.suppliers.offers.destroy', [$supplier->id, $offer->id]) }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                        <i class="bi bi-x-lg"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('admin.dss.suppliers.offers.store', $supplier->id) }}" class="lm-form-grid">
                                        @csrf
                                        <div>
                                            <label class="form-label">Produk</label>
                                            <select class="form-select" name="product_id" required>
                                                <option value="">-- pilih produk --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Harga Beli</label>
                                            <input type="number" step="0.01" min="0" class="form-control" name="price" required>
                                        </div>
                                        <div>
                                            <label class="form-label">MOQ</label>
                                            <input type="number" min="1" class="form-control" name="min_order_qty" value="{{ $supplier->min_order_qty }}" required>
                                        </div>
                                        <div>
                                            <label class="form-label">Lead Time (hari)</label>
                                            <input type="number" min="1" max="60" class="form-control" name="lead_time_days" value="{{ $supplier->lead_time_days }}" required>
                                        </div>
                                        <div class="d-flex align-items-end">
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="is_preferred" value="1" id="preferred-{{ $supplier->id }}">
                                                <label class="form-check-label" for="preferred-{{ $supplier->id }}">Pemasok pilihan</label>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-end">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-plus-lg me-1"></i>Tambah Penawaran
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================ RIWAYAT ============================ --}}
        <div data-dss-section="dss-riwayat" class="d-none">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Analisis (dss_runs)</h5>
                </div>
                <div class="card-body">
                    @if(isset($analyses) && $analyses->isEmpty())
                        <x-ui.empty-state
                            icon="bi bi-clock-history"
                            title="Belum ada riwayat analisis"
                            description='Klik "Jalankan & Simpan Analisis" untuk membuat run pertama.'
                        />
                    @elseif(isset($runs) && $runs->isEmpty())
                        <x-ui.empty-state
                            icon="bi bi-clock-history"
                            title="Belum ada riwayat analisis"
                            description='Klik "Jalankan & Simpan Analisis" untuk membuat run pertama.'
                        />
                    @else
                        <div class="lm-table-wrap">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Run</th>
                                        <th>Jenis</th>
                                        <th>Status</th>
                                        <th>Lambda Max</th>
                                        <th>CI</th>
                                        <th>RI</th>
                                        <th>CR</th>
                                        <th>Konsisten</th>
                                        <th>Dibuat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($runs as $run)
                                        <tr>
                                            <td class="lm-score">{{ $run->id }}</td>
                                            <td>{{ $run->name }}</td>
                                            <td>{{ $run->typeLabel() }}</td>
                                            <td>
                                                <span class="lm-badge {{ $run->status === 'completed' ? 'lm-badge-approved' : 'lm-badge-pending' }}">
                                                    {{ $run->status === 'completed' ? 'Selesai' : 'Draft' }}
                                                </span>
                                            </td>
                                            <td class="lm-score">{{ $run->lambda_max !== null ? number_format($run->lambda_max, 4) : '-' }}</td>
                                            <td class="lm-score">{{ $run->ci !== null ? number_format($run->ci, 4) : '-' }}</td>
                                            <td class="lm-score">{{ $run->ri !== null ? number_format($run->ri, 4) : '-' }}</td>
                                            <td class="lm-score">{{ $run->cr !== null ? number_format($run->cr, 4) : '-' }}</td>
                                            <td>
                                                @if($run->is_consistent === null)
                                                    <span class="lm-badge lm-badge-neutral">-</span>
                                                @elseif($run->is_consistent)
                                                    <span class="lm-badge lm-badge-approved">Ya</span>
                                                @else
                                                    <span class="lm-badge lm-badge-rejected">Tidak</span>
                                                @endif
                                            </td>
                                            <td>{{ $run->created_at?->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const root = document.getElementById('adminDss');
            if (!root) return;

            const sections = Array.from(root.querySelectorAll('[data-dss-section]'));
            const tabs = Array.from(root.querySelectorAll('[data-dss-tab]'));
            const defaultSection = 'dss-ringkasan';

            const activateSection = (id) => {
                if (!sections.some((section) => section.dataset.dssSection === id)) return;

                sections.forEach((section) => {
                    section.classList.toggle('d-none', section.dataset.dssSection !== id);
                });

                tabs.forEach((tab) => {
                    tab.classList.toggle('active', tab.dataset.dssTab === id);
                });

                if (window.location.hash !== `#${id}`) {
                    history.replaceState(null, '', `#${id}`);
                }

                // Modul yang baru tampil selalu dimulai dari paling atas.
                window.scrollTo(0, 0);
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', (event) => {
                    event.preventDefault();
                    activateSection(tab.dataset.dssTab);
                });
            });

            const sectionFromHash = () => {
                const id = window.location.hash.slice(1);
                activateSection(sections.some((section) => section.dataset.dssSection === id) ? id : defaultSection);
            };

            sectionFromHash();
            window.addEventListener('hashchange', sectionFromHash);
        })();
    </script>
@endpush
