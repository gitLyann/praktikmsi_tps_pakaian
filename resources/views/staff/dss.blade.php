@extends('layouts.app', [
    'title' => 'Penilaian Pemasok - DSS',
    'sidebar' => true,
    'activeRoute' => 'dss',
    'dashboardRoute' => 'staff.dashboard'
])

@section('content')
    @php
        $restock = $restock ?? ['items' => [], 'products' => 0, 'need_restock' => 0, 'total_qty' => 0, 'total_cost' => 0, 'days' => 30];
        $vikor = $vikor ?? ['scores' => [], 'v' => 0.5, 'threshold' => 0.25, 'best_classification' => '-', 'has_advantage' => false, 'is_stable' => false];
    @endphp

    <div id="staffDss" data-dss>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h3 class="mb-1">DSS Kasir (Rekomendasi Restock &amp; Penilaian Pemasok)</h3>
                <p class="text-muted mb-0">
                    Lihat produk yang perlu restock, ajukan pembelian ke Manager, lalu isi skor
                    penilaian pemasok. Bobot AHP di bawah dipakai otomatis admin saat menjalankan analisis VIKOR.
                </p>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
            </span>
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
            <div class="mb-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $errorMessage)
                            <li>{{ $errorMessage }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                </div>
            </div>
        @endif

        {{-- Navigasi tab --}}
        <ul class="nav nav-pills mb-4 flex-wrap gap-1" data-dss-tabs>
            <li class="nav-item">
                <a href="#dss-restock" class="nav-link active" data-dss-tab="dss-restock">
                    <i class="bi bi-box-seam me-1"></i>Rekomendasi Restock
                </a>
            </li>
            <li class="nav-item">
                <a href="#dss-penilaian" class="nav-link" data-dss-tab="dss-penilaian">
                    <i class="bi bi-pencil-square me-1"></i>Penilaian Supplier
                </a>
            </li>
            <li class="nav-item">
                <a href="#dss-ranking" class="nav-link" data-dss-tab="dss-ranking">
                    <i class="bi bi-trophy me-1"></i>Ranking Supplier
                </a>
            </li>
        </ul>

        {{-- ============================ REKOMENDASI RESTOCK ============================ --}}
        <div data-dss-section="dss-restock">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Rekomendasi Restock ({{ $restock['days'] }} hari terakhir)</h5>
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
                            <div class="lm-empty-text">Rekomendasi restock butuh data produk, penawaran pemasok, dan riwayat transaksi.</div>
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
                                        <th class="text-end">Aksi</th>
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
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('staff.dss.restock.store') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                                    <input type="hidden" name="jumlah_restock" value="{{ max(1, (int) $item['recommended_qty']) }}">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-send me-1"></i>Ajukan Restock
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
                    <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Status Pengajuan Restock</h5>
                </div>
                <div class="card-body">
                    @if($restockRequests->isEmpty())
                        <div class="lm-empty">
                            <i class="bi bi-inbox"></i>
                            <div class="lm-empty-title">Belum ada pengajuan restock</div>
                            <div class="lm-empty-text">Klik "Ajukan Restock" pada tabel di atas.</div>
                        </div>
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
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================ PENILAIAN SUPPLIER ============================ --}}
        <div data-dss-section="dss-penilaian" class="d-none">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Kriteria Penilaian &amp; Bobot AHP</h5>
                </div>
                <div class="card-body">
                    @forelse($criteria as $criterion)
                        <div class="lm-criterion mb-2">
                            <div class="min-w-0">
                                <div class="lm-criterion-name">{{ $criterion['code'] }} &mdash; {{ $criterion['name'] }}</div>
                                <div class="lm-criterion-type">
                                    {{ $criterion['type'] === 'benefit' ? 'Benefit (nilai besar lebih baik)' : 'Cost (nilai kecil lebih baik)' }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="lm-score">{{ number_format($criterion['weight'], 4) }}</div>
                                <div class="lm-criterion-type">bobot</div>
                            </div>
                        </div>
                    @empty
                        <div class="lm-empty">
                            <i class="bi bi-diagram-3"></i>
                            <div class="lm-empty-title">Belum ada kriteria penilaian</div>
                            <div class="lm-empty-text">Tambahkan kriteria pada tabel <code>dss_criteria</code> terlebih dahulu.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Form Penilaian</h5>
                    @if($run)
                        <span class="badge bg-light text-dark">
                            Run penilaian aktif: {{ $run->name }} (#{{ $run->id }})
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    @if($suppliers === [] || $criteria === [])
                        <div class="lm-empty">
                            <i class="bi bi-truck"></i>
                            <div class="lm-empty-title">Data penilaian belum lengkap</div>
                            <div class="lm-empty-text">
                                Form penilaian membutuhkan minimal satu pemasok dan satu kriteria penilaian.
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('staff.dss.scores.store') }}">
                            @csrf
                            <div class="lm-table-wrap">
                                <table class="table table-bordered align-middle mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 220px;">Pemasok</th>
                                            @foreach($criteria as $criterion)
                                                <th class="text-center">
                                                    {{ $criterion['code'] }}
                                                    <div class="fw-normal small text-muted">{{ $criterion['name'] }}</div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php($scoreIndex = 0)
                                        @foreach($suppliers as $supplier)
                                            <tr>
                                                <th class="text-start">
                                                    {{ $supplier['supplier_name'] }}
                                                    <div class="fw-normal small text-muted">
                                                        {{ $supplier['code'] }} &middot; lead time {{ $supplier['lead_time_days'] }} hari
                                                        &middot; MOQ {{ $supplier['min_order_qty'] }}
                                                    </div>
                                                </th>
                                                @foreach($supplier['scores'] as $score)
                                                    <td>
                                                        <input type="hidden" name="scores[{{ $scoreIndex }}][supplier_id]" value="{{ $supplier['supplier_id'] }}">
                                                        <input type="hidden" name="scores[{{ $scoreIndex }}][criterion_id]" value="{{ $score['criterion_id'] }}">
                                                        <input type="number" step="0.01" min="0"
                                                            class="form-control form-control-sm"
                                                            name="scores[{{ $scoreIndex }}][score]"
                                                            value="{{ $score['score'] !== null ? $score['score'] : '' }}"
                                                            placeholder="-">
                                                    </td>
                                                    @php($scoreIndex++)
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <p class="text-muted small mb-0">
                                    Kosongkan sel yang belum dinilai. Skor memakai nilai mentah kriteria
                                    (mis. harga dalam rupiah, lead time dalam hari, rating 1-5).
                                </p>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Simpan Penilaian
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============================ RANKING SUPPLIER (READ ONLY) ============================ --}}
        <div data-dss-section="dss-ranking" class="d-none">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Ranking Supplier (Read Only)</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Ranking dihitung otomatis dari skor penilaian memakai metode VIKOR. Data ini hanya
                        dibaca, perhitungannya dilakukan admin saat menjalankan analisis.
                    </p>

                    @if($vikor['scores'] === [])
                        <div class="lm-empty">
                            <i class="bi bi-clipboard-data"></i>
                            <div class="lm-empty-title">Belum ada skor pemasok</div>
                            <div class="lm-empty-text">Isi form penilaian terlebih dahulu di tab "Penilaian Supplier".</div>
                        </div>
                    @else
                        <div class="lm-metric-row mb-4">
                            <div class="lm-metric">
                                <div class="lm-metric-label">Bobot v</div>
                                <div class="lm-metric-value">{{ number_format($vikor['v'], 2) }}</div>
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
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const root = document.getElementById('staffDss');
            if (!root) return;

            const sections = Array.from(root.querySelectorAll('[data-dss-section]'));
            const tabs = Array.from(root.querySelectorAll('[data-dss-tab]'));
            const defaultSection = 'dss-restock';

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
