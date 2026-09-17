<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Staff - OAS Restock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">TPS & OAS - Staff/Kasir Panel</a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white">Halo, {{ Auth::user()->name }} (Staff)</span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Notifikasi Sukses -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- Formulir Pengajuan Restock (Fungsi OAS Staff) -->
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark fw-bold">
                        Form Pengajuan Restock Barang (OAS)
                    </div>
                    <div class="card-body">
                        <form action="{{ route('kasir.restock.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Pilih Produk</label>
                                <select name="product_id" class="form-select" required>
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->name }} (Stok Saat Ini: {{ $product->stock }})
                                        </option>
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

            <!-- Tabel Riwayat Pengajuan Restock -->
            <div class="col-md-7 mb-4">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></script>
</body>
</html>
