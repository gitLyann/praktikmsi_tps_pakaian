@extends('layouts.app', [
    'title' => 'Dashboard Manager - OAS Approval',
    'sidebar' => true,
    'activeRoute' => request()->segment(2) ?? 'dashboard',
    'dashboardRoute' => 'admin.dashboard'
])

@section('content')
    <!-- Notifikasi Sukses -->
    @if(session('success'))
        <div class="mb-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- Notifikasi Error -->
    @if(session('error'))
        <div class="mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="mb-0"><i class="bi bi-check-circle me-2"></i>Dashboard Manager</h3>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.dashboard') }}#kelola-produk" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-box-seam me-1"></i> Kelola Produk
            </a>
        </div>
    </div>

    {{-- Header khusus tampilan cetak (muncul hanya di @media print) --}}
    <div class="print-header">
        <h4 class="mb-1">Laporan MIS - La Mamora</h4>
        <p class="mb-0">Periode {{ $periode['dari_label'] }} - {{ $periode['sampai_label'] }} · Dicetak {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- ================= SECTION: DASHBOARD (Sekilas Lintas Menu) ================= --}}
    <div class="dashboard-section" id="dashboard" data-lm-title="Dashboard Manager">
        {{-- KPI Executive Summary --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="lm-stat-icon is-success">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>
                        <div>
                            <div class="lm-stat-label">Total Omset</div>
                            <div class="lm-stat-value">Rp {{ number_format($kpi['omset'], 0, ',', '.') }}</div>
                            <div class="lm-stat-note">Periode berjalan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="lm-stat-icon">
                            <i class="bi bi-cart-check fs-4"></i>
                        </div>
                        <div>
                            <div class="lm-stat-label">Produk Terjual</div>
                            <div class="lm-stat-value">{{ number_format($kpi['produk_terjual']) }} pcs</div>
                            <div class="lm-stat-note">Dari transaction_details</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="lm-stat-icon is-warning">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                        <div>
                            <div class="lm-stat-label">Stok Kritis</div>
                            <div class="lm-stat-value">{{ $kpi['stok_kritis'] }} produk</div>
                            <div class="lm-stat-note">Stok &lt; 5 pcs</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="lm-stat-icon is-info">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                        <div>
                            <div class="lm-stat-label">Restock Pending</div>
                            <div class="lm-stat-value">{{ $kpi['restock_pending'] }} pengajuan</div>
                            <div class="lm-stat-note">Menunggu keputusan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Widget sekilas: cuplikan dari tiap menu OAS & MIS --}}
        @php($pendingList = $restockRequests->where('status', 'pending'))
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-warning bg-opacity-10 text-dark fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-exclamation-triangle me-2"></i>Stok Kritis</span>
                        <span class="badge bg-warning text-dark">{{ $lowStock->count() }} produk</span>
                    </div>
                    <div class="card-body">
                        @if($lowStock->isEmpty())
                            <p class="text-muted small mb-2"><i class="bi bi-check-circle me-1"></i>Semua stok aman - tidak ada produk di bawah 5 pcs.</p>
                        @else
                            <ul class="list-unstyled mb-2">
                                @foreach($lowStock->take(3) as $item)
                                    <li class="d-flex justify-content-between align-items-center small py-1 border-bottom">
                                        <span class="fw-semibold">{{ $item->name }}</span>
                                        <span class="fw-bold text-danger">{{ $item->stock }} pcs</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('admin.dashboard') }}#stok-realtime" class="small fw-semibold text-decoration-none">Lihat selengkapnya →</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-info bg-opacity-10 text-dark fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-hourglass-split me-2"></i>Restock Pending</span>
                        <span class="badge bg-info text-dark">{{ $pendingList->count() }} pengajuan</span>
                    </div>
                    <div class="card-body">
                        @if($pendingList->isEmpty())
                            <p class="text-muted small mb-2"><i class="bi bi-check-circle me-1"></i>Tidak ada pengajuan menunggu keputusan.</p>
                        @else
                            <ul class="list-unstyled mb-2">
                                @foreach($pendingList->take(3) as $req)
                                    <li class="d-flex justify-content-between align-items-center small py-1 border-bottom">
                                        <span class="fw-semibold">{{ $req->product->name ?? 'Produk N/A' }}</span>
                                        <span class="text-muted">{{ $req->jumlah_restock }} pcs</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('admin.dashboard') }}#persetujuan-restock" class="small fw-semibold text-decoration-none">Lihat selengkapnya →</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary bg-opacity-10 text-primary fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-trophy me-2"></i>Top Produk</span>
                        <span class="badge bg-primary text-white">Periode berjalan</span>
                    </div>
                    <div class="card-body">
                        @if($topProduk->isEmpty())
                            <p class="text-muted small mb-2">Belum ada data penjualan pada periode ini.</p>
                        @else
                            <ul class="list-unstyled mb-2">
                                @foreach($topProduk->take(3) as $top)
                                    <li class="d-flex justify-content-between align-items-center small py-1 border-bottom">
                                        <span class="fw-semibold">{{ $top->nama }}</span>
                                        <span class="text-muted">{{ (int) $top->terjual }} pcs</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('admin.dashboard') }}#analisis-mis" class="small fw-semibold text-decoration-none">Buka Analisis Penjualan →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= SECTION: ANALISIS PENJUALAN (MIS) ================= --}}
    <div id="analisis-mis" class="dashboard-section d-none" data-lm-title="Analisis Penjualan">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Analisis Penjualan</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-white text-primary">Periode {{ $periode['dari_label'] }} - {{ $periode['sampai_label'] }}</span>
                    <button type="button" class="btn btn-light btn-sm no-print" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Cetak / Export Laporan
                    </button>
                </div>
            </div>
            <div class="card-body">
                {{-- Filter tanggal (Custom Report Generator) --}}
                <form action="{{ route('admin.dashboard') }}" method="GET" class="row g-2 align-items-end mb-4 no-print">
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filterDari" class="form-label small text-muted mb-1">Dari Tanggal</label>
                        <input type="date" name="dari" id="filterDari" class="form-control" value="{{ $periode['dari_input'] }}">
                    </div>
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filterSampai" class="form-label small text-muted mb-1">Sampai Tanggal</label>
                        <input type="date" name="sampai" id="filterSampai" class="form-control" value="{{ $periode['sampai_input'] }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                    <div class="col-auto ms-md-auto">
                        <span class="text-muted small">Default: 6 bulan terakhir</span>
                    </div>
                </form>

                {{-- Grafik MIS --}}
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-light fw-bold">
                                <i class="bi bi-graph-up me-2"></i>Tren Penjualan Bulanan
                            </div>
                            <div class="card-body">
                                @if(count($trenLabels))
                                    <div style="position:relative;height:300px">
                                        <canvas id="chartTren"></canvas>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-5">Tidak ada periode untuk ditampilkan.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header bg-light fw-bold">
                                <i class="bi bi-pie-chart me-2"></i>Produk Terlaris (Top 5)
                            </div>
                            <div class="card-body">
                                @if($topProduk->isNotEmpty())
                                    <div style="position:relative;height:300px">
                                        <canvas id="chartTopProduk"></canvas>
                                    </div>
                                    <table class="table table-sm table-borderless mb-0 mt-2">
                                        <tbody>
                                            @foreach($topProduk as $top)
                                                <tr>
                                                    <td class="ps-0 small">{{ $top->nama }}</td>
                                                    <td class="text-end small fw-semibold">{{ (int) $top->terjual }} pcs</td>
                                                    <td class="text-end small text-muted pe-0">Rp {{ number_format((float) $top->omset, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada data <code>transaction_details</code> pada periode ini,
                                        jadi diagram produk terlaris belum bisa dibuat.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ===== Exception Reporting: Low Stock Alert (stok < 5 pcs) ===== --}}
    <div id="stok-realtime" class="dashboard-section d-none mb-4" data-lm-title="Monitoring Stok Realtime">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Peringatan Stok Menipis (Stok &lt; 5 pcs)</h5>
                <span class="badge bg-dark">{{ $lowStock->count() }} produk</span>
            </div>
            <div class="card-body">
                @if($lowStock->isEmpty())
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle me-2"></i>Semua stok aman - tidak ada produk dengan stok di bawah 5 pcs.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Sisa Stok</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStock as $product)
                                    <tr>
                                        <td>{{ $product->id }}</td>
                                        <td class="fw-semibold">{{ $product->name }}</td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $product->category->name ?? '-' }}</span></td>
                                        <td class="text-end">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                        <td class="text-center fw-bold text-danger">{{ $product->stock }} pcs</td>
                                        <td class="text-center">
                                            @if($product->stock <= 0)
                                                <span class="badge bg-danger">Habis</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Kritis</span>
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

    {{-- ================= KELOLA PRODUK (KMS) =================
         Form "Tambah Master Produk" sengaja disembunyikan: halaman Manager fokus
         pada RUD (lihat, ubah, hapus) + Approval OAS. Penambahan master produk
         dilakukan lewat dashboard Staff. --}}
    <div id="kelola-produk" class="dashboard-section d-none" data-lm-title="Kelola Produk">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Daftar Master Produk</h5>
                <span class="badge bg-light text-dark">{{ $products->count() }} produk</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Foto</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        @if($product->image)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                                style="width:48px;height:48px;object-fit:cover;border-radius:8px">
                                        @else
                                            <span class="text-muted small">Tanpa foto</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $product->name }}
                                        @if($product->description)
                                            <div class="text-muted small fw-normal">
                                                {{ Str::limit($product->description, 70) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $product->category->name ?? '-' }}</span></td>
                                    <td>{{ $product->type ?? '-' }}</td>
                                    <td>{{ $product->size ?? '-' }}</td>
                                    <td>{{ $product->color ?? '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                    <td class="text-center fw-semibold">{{ $product->stock }}</td>
                                    <td class="text-center text-nowrap">
                                        {{-- Preview read-only produk, ditampilkan lewat overlay di dalam
                                             halaman ini supaya Manager tidak berpindah ke halaman
                                             publik /toko/produk/{id}. --}}
                                        <button type="button" class="btn btn-outline-info btn-sm btn-lihat-produk-admin"
                                            data-lihat-produk="{{ $product->id }}"
                                            data-nama="{{ $product->name }}"
                                            data-kategori="{{ $product->category->name ?? '-' }}"
                                            data-type="{{ $product->type ?? '-' }}"
                                            data-size="{{ $product->size ?? '-' }}"
                                            data-color="{{ $product->color ?? '-' }}"
                                            data-harga="Rp {{ number_format($product->price, 0, ',', '.') }}"
                                            data-stok="{{ (int) $product->stock }}"
                                            data-deskripsi="{{ $product->description ?? '' }}"
                                            data-gambar="{{ $product->image_url ?? '' }}"
                                            title="Lihat detail produk">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        {{-- Data form edit dibaca JS dari atribut data-* agar tidak perlu request ulang --}}
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-edit-produk-admin"
                                            data-edit-produk="{{ $product->id }}"
                                            data-nama="{{ $product->name }}"
                                            data-kategori="{{ $product->category->name ?? '' }}"
                                            data-type="{{ $product->type ?? '' }}"
                                            data-size="{{ $product->size ?? '' }}"
                                            data-color="{{ $product->color ?? '' }}"
                                            data-harga="{{ (int) $product->price }}"
                                            data-deskripsi="{{ $product->description ?? '' }}"
                                            data-gambar="{{ $product->image_url ?? '' }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Hapus produk &quot;{{ $product->name }}&quot;? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus produk">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">Belum ada produk. Master produk ditambahkan dari dashboard Staff.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= PERSETUJUAN RESTOCK (OAS) ================= --}}
    <div id="persetujuan-restock" class="dashboard-section d-none" data-lm-title="Persetujuan Restock">
        <h4 class="mb-3"><i class="bi bi-check-circle me-2"></i>Persetujuan Restock Barang (OAS)</h4>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($restockRequests as $req)
                                <tr>
                                    <td>{{ $req->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>{{ $req->product->name ?? 'Produk N/A' }}</td>
                                    <td>{{ $req->jumlah_restock }} pcs</td>
                                    <td>
                                        @if($req->status === 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($req->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->status === 'pending')
                                            <form action="{{ route('admin.restock.update', $req->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg me-1"></i>Approve</button>
                                            </form>
                                            <form action="{{ route('admin.restock.update', $req->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-x-lg me-1"></i>Reject</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada pengajuan restock.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MIS: AUDIT TRAIL / ACTIVITY LOG RESTOCK ================= --}}
    <div id="riwayat-stok" class="dashboard-section d-none" data-lm-title="Riwayat Stok">
        <h4 class="mb-3"><i class="bi bi-clock-history me-2"></i>Audit Trail Pengajuan Restock</h4>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Riwayat Pengajuan &amp; Status Approval</h5>
                <span class="badge bg-light text-dark">{{ $auditTrail->count() }} catatan</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Pengaju (Staff)</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-center">Status Approval</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditTrail as $log)
                                <tr>
                                    <td class="text-nowrap">{{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="fw-semibold">{{ $log->product->name ?? 'Produk N/A' }}</td>
                                    <td>
                                        {{ $log->employee->name ?? 'Staff N/A' }}
                                        @if(optional($log->employee)->position)
                                            <div class="text-muted small">{{ $log->employee->position }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $log->jumlah_restock }} pcs</td>
                                    <td class="text-center">
                                        @if($log->status === 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($log->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $log->catatan ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada aktivitas restock tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= OVERLAY EDIT PRODUK =================
         Sama seperti di halaman staff, overlay memakai CSS kustom (position:fixed)
         supaya tidak bergantung pada perilaku modal Bootstrap. --}}
    <div class="produk-overlay" id="editProdukAdminOverlay" role="dialog" aria-modal="true" aria-labelledby="editProdukAdminTitle" hidden>
        <div class="produk-overlay-box">
            <div class="produk-overlay-head">
                <h5 class="mb-0" id="editProdukAdminTitle">
                    <i class="bi bi-pencil-square me-2"></i>Edit Produk
                </h5>
                <button type="button" class="btn-close" id="editProdukAdminClose" aria-label="Tutup"></button>
            </div>

            <form action="" method="POST" id="editProdukAdminForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <input type="hidden" name="_produk_id" id="editProdukAdminId" value="">

                <div class="produk-overlay-body">
                    <div id="editProdukAdminError" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="editAdminNama" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editAdminNama" class="form-control" maxlength="100" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminKategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="category" id="editAdminKategori" class="form-control" maxlength="50"
                                list="editAdminKategoriList" required>
                            <datalist id="editAdminKategoriList">
                                @foreach($categories as $category)
                                    <option value="{{ $category->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminType" class="form-label">Type</label>
                            <input type="text" name="type" id="editAdminType" class="form-control" maxlength="50">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminSize" class="form-label">Size</label>
                            <input type="text" name="size" id="editAdminSize" class="form-control" maxlength="10">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminColor" class="form-label">Color</label>
                            <input type="text" name="color" id="editAdminColor" class="form-control" maxlength="30">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminHarga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="editAdminHarga" class="form-control" min="0" step="1" required>
                        </div>

                        <div class="col-12">
                            <label for="editAdminDeskripsi" class="form-label">Deskripsi Produk</label>
                            <textarea name="description" id="editAdminDeskripsi" rows="4" class="form-control" maxlength="2000"></textarea>
                            <div class="form-text">Maksimal 2000 karakter. Tampil di halaman detail produk pelanggan.</div>
                        </div>

                        <div class="col-12">
                            <label for="editAdminImage" class="form-label">Ganti Foto Produk</label>
                            <input type="file" name="image" id="editAdminImage" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Kosongkan bila tidak ingin mengganti foto.</div>

                            <div class="d-flex align-items-center gap-2 mt-2">
                                <img id="editAdminImagePreview" src="" alt="Foto produk saat ini" class="produk-preview d-none">
                                <span id="editAdminImageInfo" class="text-muted small">Produk ini belum memiliki foto.</span>
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="editAdminRemoveImage" name="remove_image">
                                <label class="form-check-label" for="editAdminRemoveImage">Hapus foto produk ini</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Stok tidak dapat diubah dari form ini. Perubahan stok hanya dilakukan lewat persetujuan restock.
                    </div>
                </div>

                <div class="produk-overlay-foot">
                    <button type="button" class="btn btn-outline-secondary" id="editProdukAdminBatal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= OVERLAY LIHAT PRODUK (READ-ONLY) =================
         Tanpa <form> sama sekali, hanya teks baca-saja dan satu tombol Tutup,
         sehingga Manager tidak bisa mengedit atau menghapus dari sini. --}}
    <div class="produk-overlay" id="lihatProdukAdminOverlay" role="dialog" aria-modal="true" aria-labelledby="lihatProdukAdminTitle" hidden>
        <div class="produk-overlay-box">
            <div class="produk-overlay-head">
                <h5 class="mb-0" id="lihatProdukAdminTitle">
                    <i class="bi bi-eye me-2"></i>Lihat Produk
                </h5>
            </div>

            <div class="produk-overlay-body">
                <div class="produk-detail-media">
                    <img id="lihatProdukAdminFoto" class="produk-detail-photo d-none" alt="Foto produk">
                    <div id="lihatProdukAdminTanpaFoto" class="produk-detail-fallback">
                        <i class="bi bi-image me-2"></i>Produk ini belum punya foto
                    </div>
                </div>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Nama Produk</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminNama">-</dd>

                    <dt class="col-sm-4">Kategori</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminKategori">-</dd>

                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminType">-</dd>

                    <dt class="col-sm-4">Size</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminSize">-</dd>

                    <dt class="col-sm-4">Color</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminColor">-</dd>

                    <dt class="col-sm-4">Harga</dt>
                    <dd class="col-sm-8 fw-semibold" id="lihatProdukAdminHarga">-</dd>

                    <dt class="col-sm-4">Stok</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminStok">-</dd>
                </dl>

                <hr class="my-3">

                <h6 class="mb-2">Deskripsi Produk</h6>
                <div id="lihatProdukAdminDeskripsi" class="produk-detail-deskripsi">Belum ada deskripsi untuk produk ini.</div>

                <div class="alert alert-secondary mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Halaman ini hanya menampilkan data. Untuk mengubah produk gunakan tombol pensil di
                    baris tabel, dan penambahan stok dilakukan lewat persetujuan restock.
                </div>
            </div>

            <div class="produk-overlay-foot">
                <button type="button" class="btn btn-outline-secondary" id="lihatProdukAdminTutup">Tutup</button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Hanya tampil saat dicetak (window.print) */
        .print-header { display: none; }

        @media print {
            .sidebar,
            .sidebar-overlay,
            .no-print,
            form,
            .btn,
            .produk-overlay { display: none !important; }

            .main-content { padding: 0; background: #fff; }
            body { background: #fff; }

            .print-header { display: block; margin-bottom: 16px; }

            .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; break-inside: avoid; }
            .card-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            a[href]:after { content: none !important; }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        /* ============ PEMILIH SECTION (pola sama dengan dashboard staff) ============ */
        (function () {
            const sections = Array.from(document.querySelectorAll('.dashboard-section'));
            if (!sections.length) return;

            // Hanya link sidebar yang hash-nya menunjuk ke section nyata.
            // Link Profil/Pengaturan (modal Bootstrap) sengaja tidak ikut
            // supaya preventDefault tidak mematikan trigger modal.
            const navLinks = Array.from(document.querySelectorAll('.sidebar-nav .nav-link')).filter(link => {
                const hash = (link.getAttribute('href') || '').split('#')[1] || '';
                return Boolean(hash) && sections.some(section => section.id === hash);
            });

            const defaultSectionId = () => {
                // Baru submit filter tanggal MIS -> langsung ke menu Analisis Penjualan
                if (window.location.search) return 'analisis-mis';
                return 'dashboard';
            };

            const activateSection = id => {
                if (!sections.some(section => section.id === id)) return;

                sections.forEach(section => {
                    section.classList.toggle('d-none', section.id !== id);
                });

                navLinks.forEach(link => {
                    link.classList.toggle('active', (link.getAttribute('href') || '').endsWith('#' + id));
                });

                if (window.location.hash !== '#' + id) {
                    history.replaceState(null, '', '#' + id);
                }

                // Section yang baru tampil selalu dimulai dari paling atas.
                window.scrollTo(0, 0);

                // Canvas chart dibuat saat container masih display:none,
                // jadi paksa resize setelah sectionnya tampil.
                if (typeof Chart !== 'undefined') {
                    document.querySelectorAll('.dashboard-section canvas').forEach(canvas => {
                        const instance = Chart.getChart(canvas);
                        if (instance) instance.resize();
                    });
                }
            };

            navLinks.forEach(link => {
                link.addEventListener('click', event => {
                    event.preventDefault();
                    activateSection((link.getAttribute('href') || '').split('#')[1]);

                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    if (sidebar && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                        if (overlay) overlay.classList.remove('show');
                    }
                });
            });

            const sectionFromHash = () => {
                const id = window.location.hash.slice(1);
                activateSection(sections.some(section => section.id === id) ? id : defaultSectionId());
            };

            sectionFromHash();
            window.addEventListener('hashchange', sectionFromHash);
        })();
    </script>
    <script>
        (function () {
            if (typeof Chart === 'undefined') return;

            /* Tren penjualan bulanan: bar Omset (sumbu kiri) + line Produk Terjual (sumbu kanan) */
            const tren = document.getElementById('chartTren');
            if (tren) {
                new Chart(tren, {
                    data: {
                        labels: @json($trenLabels),
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Omset (Rp)',
                                data: @json($trenOmset),
                                backgroundColor: 'rgba(13, 110, 253, .65)',
                                borderColor: 'rgba(13, 110, 253, 1)',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                type: 'line',
                                label: 'Produk Terjual (pcs)',
                                data: @json($trenTerjual),
                                borderColor: 'rgba(255, 165, 0, 1)',
                                backgroundColor: 'rgba(255, 165, 0, .15)',
                                tension: .3,
                                fill: false,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                position: 'left',
                                ticks: {
                                    callback: value => 'Rp ' + new Intl.NumberFormat('id-ID').format(value)
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: { drawOnChartArea: false }
                            }
                        }
                    }
                });
            }

            /* Diagram produk terlaris (hanya dirender bila datanya ada) */
            const top = document.getElementById('chartTopProduk');
            if (top) {
                const WARNA = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1'];
                new Chart(top, {
                    type: 'doughnut',
                    data: {
                        labels: @json($topProduk->pluck('nama')),
                        datasets: [{
                            data: @json($topProduk->pluck('terjual')->map(fn ($v) => (int) $v)),
                            backgroundColor: WARNA
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
        })();
    </script>
    <script>
        (function () {
            const overlay = document.getElementById('editProdukAdminOverlay');
            const form = document.getElementById('editProdukAdminForm');
            if (!overlay || !form) return;

            const fields = {
                nama: document.getElementById('editAdminNama'),
                kategori: document.getElementById('editAdminKategori'),
                type: document.getElementById('editAdminType'),
                size: document.getElementById('editAdminSize'),
                color: document.getElementById('editAdminColor'),
                harga: document.getElementById('editAdminHarga'),
                deskripsi: document.getElementById('editAdminDeskripsi'),
                image: document.getElementById('editAdminImage'),
                removeImage: document.getElementById('editAdminRemoveImage'),
                preview: document.getElementById('editAdminImagePreview'),
                info: document.getElementById('editAdminImageInfo'),
                error: document.getElementById('editProdukAdminError'),
                produkId: document.getElementById('editProdukAdminId'),
            };
            const EDIT_URL = @json(route('admin.products.update', ['product' => 0]));

            const setPreview = (src) => {
                if (src) {
                    fields.preview.src = src;
                    fields.preview.classList.remove('d-none');
                    fields.info.classList.add('d-none');
                } else {
                    fields.preview.removeAttribute('src');
                    fields.preview.classList.add('d-none');
                    fields.info.classList.remove('d-none');
                }
            };

            const openOverlay = (btn) => {
                form.action = EDIT_URL + btn.dataset.editProduk;
                fields.produkId.value = btn.dataset.editProduk || '';
                fields.nama.value = btn.dataset.nama || '';
                fields.kategori.value = btn.dataset.kategori || '';
                fields.type.value = btn.dataset.type || '';
                fields.size.value = btn.dataset.size || '';
                fields.color.value = btn.dataset.color || '';
                fields.harga.value = btn.dataset.harga || '';
                fields.deskripsi.value = btn.dataset.deskripsi || '';
                fields.image.value = '';
                fields.removeImage.checked = false;
                fields.image.disabled = false;
                fields.error.classList.add('d-none');
                setPreview(btn.dataset.gambar || '');

                overlay.hidden = false;
                document.body.style.overflow = 'hidden';
                fields.nama.focus();
            };

            const closeOverlay = () => {
                overlay.hidden = true;
                document.body.style.overflow = '';
            };

            document.querySelectorAll('.btn-edit-produk-admin').forEach(btn => {
                btn.addEventListener('click', () => openOverlay(btn));
            });

            document.getElementById('editProdukAdminClose').addEventListener('click', closeOverlay);
            document.getElementById('editProdukAdminBatal').addEventListener('click', closeOverlay);
            overlay.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(); });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !overlay.hidden) closeOverlay();
            });

            // Mencentang "hapus foto" menonaktifkan input file supaya tidak
            // ada dua aksi yang bertabrakan dalam satu submit.
            fields.removeImage.addEventListener('change', () => {
                setPreview('');
                fields.image.disabled = fields.removeImage.checked;
            });

            // Kalau validasi update produk gagal, form dikembalikan ke halaman ini.
            // Overlay langsung dibuka lagi dengan pesan error supaya Manager
            // tidak perlu mencari produknya satu per satu.
            @if($errors->any() && old('_produk_id'))
                const btn = document.querySelector('.btn-edit-produk-admin[data-edit-produk="{{ old('_produk_id') }}"]');
                if (btn) {
                    openOverlay(btn);
                    fields.error.textContent = @json($errors->first());
                    fields.error.classList.remove('d-none');
                }
            @endif
        })();

        /* ================= OVERLAY LIHAT PRODUK (READ-ONLY) ================= */
        (function () {
            const lihatOverlay = document.getElementById('lihatProdukAdminOverlay');
            const editOverlay = document.getElementById('editProdukAdminOverlay');
            if (!lihatOverlay) return;

            const lihatFields = {
                nama: document.getElementById('lihatProdukAdminNama'),
                kategori: document.getElementById('lihatProdukAdminKategori'),
                type: document.getElementById('lihatProdukAdminType'),
                size: document.getElementById('lihatProdukAdminSize'),
                color: document.getElementById('lihatProdukAdminColor'),
                harga: document.getElementById('lihatProdukAdminHarga'),
                stok: document.getElementById('lihatProdukAdminStok'),
                deskripsi: document.getElementById('lihatProdukAdminDeskripsi'),
                foto: document.getElementById('lihatProdukAdminFoto'),
                tanpaFoto: document.getElementById('lihatProdukAdminTanpaFoto'),
            };
            const KOSONG = '-';
            const DESKRIPSI_KOSONG = 'Belum ada deskripsi untuk produk ini.';
            let triggerTerakhir = null;

            // textContent dipakai agar nama/deskripsi berisi tanda kutip atau
            // tag tidak di-parse sebagai HTML.
            const setTeks = (el, nilai, fallback) => {
                const isi = (nilai || '').trim();
                el.textContent = isi && isi !== KOSONG ? isi : fallback;
                el.classList.toggle('produk-detail-kosong', !isi || isi === KOSONG);
            };

            const setFoto = (src) => {
                if (src) {
                    lihatFields.foto.src = src;
                    lihatFields.foto.classList.remove('d-none');
                    lihatFields.tanpaFoto.classList.add('d-none');
                } else {
                    lihatFields.foto.removeAttribute('src');
                    lihatFields.foto.classList.add('d-none');
                    lihatFields.tanpaFoto.classList.remove('d-none');
                }
            };

            const openLihatOverlay = (btn) => {
                setTeks(lihatFields.nama, btn.dataset.nama, KOSONG);
                setTeks(lihatFields.kategori, btn.dataset.kategori, KOSONG);
                setTeks(lihatFields.type, btn.dataset.type, KOSONG);
                setTeks(lihatFields.size, btn.dataset.size, KOSONG);
                setTeks(lihatFields.color, btn.dataset.color, KOSONG);
                setTeks(lihatFields.harga, btn.dataset.harga, KOSONG);
                setTeks(lihatFields.stok, btn.dataset.stok, KOSONG);
                setTeks(lihatFields.deskripsi, btn.dataset.deskripsi, DESKRIPSI_KOSONG);
                setFoto(btn.dataset.gambar || '');

                if (editOverlay && !editOverlay.hidden) {
                    editOverlay.hidden = true;
                }

                triggerTerakhir = btn;
                lihatOverlay.hidden = false;
                document.body.style.overflow = 'hidden';
                document.getElementById('lihatProdukAdminTutup').focus();
            };

            const closeLihatOverlay = () => {
                lihatOverlay.hidden = true;
                document.body.style.overflow = '';
                if (triggerTerakhir) triggerTerakhir.focus();
                triggerTerakhir = null;
            };

            document.querySelectorAll('.btn-lihat-produk-admin').forEach(btn => {
                btn.addEventListener('click', () => openLihatOverlay(btn));
            });

            document.getElementById('lihatProdukAdminTutup').addEventListener('click', closeLihatOverlay);
            lihatOverlay.addEventListener('click', (e) => { if (e.target === lihatOverlay) closeLihatOverlay(); });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !lihatOverlay.hidden) closeLihatOverlay();
            });
        })();
    </script>
@endpush
