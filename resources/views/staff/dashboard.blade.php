<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Staff - OAS Restock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- 2-Column Layout: Sidebar + Content -->
<div class="container-fluid">
    <div class="row">

        <!-- ===== SIDEBAR KIRI ===== -->
        <div class="col-md-3 col-lg-2 bg-dark text-white p-3 min-vh-100">
            <h4 class="text-center fw-bold pb-3 border-bottom border-secondary">TPS & OAS</h4>
            <hr class="mb-3 opacity-50">

            <nav class="nav flex-column text-white">
                <li class="nav-item">
                    <a class="nav-link text-white" href="/staff/dashboard">
                        <i class="bi bi-speedometer me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#stok-produk">
                        <i class="bi bi-grid me-2"></i> Monitoring Stok Produk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#pengajuan-restock">
                        <i class="bi bi-box me-2"></i> Pengajuan Restock
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#riwayat-restock">
                        <i class="bi bi-clock me-2"></i> Riwayat Restock
                    </a>
                </li>
            </nav>

            <hr class="mb-3 opacity-50">

            <!-- Logout Form (POST method) -->
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-light btn-sm text-dark">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </button>
            </form>
        </div>

        <!-- ===== AREA KONTAI UTAMA ===== -->
        <div class="col-md-9 col-lg-10 p-4">

            <!-- Alert Success -->
            @if(session('success'))
                <div class="col-12 mb-3">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            <!-- ===== SECTION 1: Monitoring Stok Produk Realtime ===== -->
            <div class="row" id="stok-produk">
                <div class="col-12 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white fw-bold">
                            <h5 class="mb-0">Ringkasan Stok Produk</h5>
                        </div>
                        <div class="card-body">
                            @forelse($products as $product)
                                <div class="col-6 col-md-4 mb-3">
                                    <div class="card h-100 shadow-sm border-0">
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title fw-bold">{{ $product->name }}</h6>
                                            <p class="card-text small text-muted">Harga: Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                            <p class="card-text small text-muted">Stok: {{ $product->stock }}</p>
                                            <span>
                                                @if($product->stock <= 5)
                                                    <span class="badge bg-danger ms-2">Stok Menipis</span>
                                                @else
                                                    <span class="badge bg-success ms-2">Stok Aman</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-info text-center">Belum ada produk yang tersedia.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SECTION 2: Form Pengajuan Restock Baru ===== -->
            <div class="row" id="pengajuan-restock" mt-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark fw-bold">
                            Form Pengajuan Restock Barang (OAS)
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.restock.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Pilih Produk</label>
                                    <select name="product_id" class="form-select" required>
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} (Stok Saat Ini: {{ $product->stock }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Restock (Qty)</label>
                                    <input type="number" name="jumlah_restock" class="form-control" min="1" placeholder="Masukkan jumlah" required>
                                </div>
                                <button type="submit" class="btn btn-warning w-100 fw-bold">Kirim Pengajuan ke Manager</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SECTION 3: Tabel Riwayat Pengajuan Restock ===== -->
            <div class="row" id="riwayat-restock" mt-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white fw-bold">
                            Riwayat Pengajuan Restock
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Produk</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($restockRequests as $req)
                                            <tr>
                                                <td>{{ $req->created_at->format('d/m/Y H:i') }}</td>
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
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada pengajuan restock.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>