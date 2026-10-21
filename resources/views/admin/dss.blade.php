@extends('layouts.app', [
    'title' => 'Analisis DSS - AHP & VIKOR',
    'sidebar' => true,
    'activeRoute' => 'dss',
    'dashboardRoute' => 'admin.dashboard'
])

@section('content')
    @if(session('success'))
        <div class="mb-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @php
        $ahp = $analysis['ahp'];
        $vikor = $analysis['vikor'];
        $restock = $analysis['restock'];
    @endphp

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

    {{-- Ringkasan AHP --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>AHP &mdash; Bobot Kriteria dan Uji Konsistensi</h5>
        </div>
        <div class="card-body">
            <div class="lm-metric-row mb-4">
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

    {{-- VIKOR --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>VIKOR &mdash; Peringkat Pemasok</h5>
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
                                <th>Klasifikasi</th>
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

    {{-- Rekomendasi restock --}}
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
                                <th>Reorder Point</th>
                                <th>Saran Qty</th>
                                <th>Estimasi Biaya</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($restock['items'] as $item)
                                <tr>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td>{{ $item['supplier_name'] ?? '-' }}</td>
                                    <td class="lm-score">{{ $item['current_stock'] }}</td>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Riwayat run --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Analisis</h5>
        </div>
        <div class="card-body">
            @if($runs->isEmpty())
                <div class="lm-empty">
                    <i class="bi bi-clock-history"></i>
                    <div class="lm-empty-title">Belum ada riwayat analisis</div>
                    <div class="lm-empty-text">Klik "Jalankan &amp; Simpan Analisis" untuk membuat run pertama.</div>
                </div>
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

    {{-- Skor penilaian yang dipakai VIKOR --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white fw-bold">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>Data Skor Penilaian Pemasok</h5>
        </div>
        <div class="card-body">
            @if($suppliers === [])
                <div class="lm-empty">
                    <i class="bi bi-truck"></i>
                    <div class="lm-empty-title">Belum ada pemasok</div>
                    <div class="lm-empty-text">Data pemasok dipakai sebagai alternatif dalam VIKOR.</div>
                </div>
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
@endsection
