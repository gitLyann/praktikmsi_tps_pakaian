<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Manager - OAS Approval</title>
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
                    <a class="nav-link text-white" href="/admin/dashboard">
                        <i class="bi bi-speedometer me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">
                        <i class="bi bi-grid me-2"></i> Monitoring Stok Realtime
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">
                        <i class="bi bi-receipt me-2"></i> Persetujuan Restock (Approval)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">
                        <i class="bi bi-clock me-2"></i> Riwayat Restock
                    </a>
                </li>
            </nav>
            
            <hr class="mb-3 opacity-50">
            
            <div class="d-grid gap-2">
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-light btn-sm text-dark">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- ===== AREA KONTAI UTAMA ===== -->
        <div class="col-md-9 col-lg-10 p-4">
            <!-- Notifikasi Sukses -->
            @if(session('success'))
                <div class="col-12 mb-3">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            <h3 class="mb-3">Persetujuan Restock Barang (OAS)</h3>
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
                                        <td>
                                            @if($req->status === 'pending')
                                                <form action="{{ route('admin.restock.update', $req->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                                <form action="{{ route('admin.restock.update', $req->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>