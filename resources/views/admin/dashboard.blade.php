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
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-check-circle me-2"></i>Persetujuan Restock Barang (OAS)</h3>
        <a href="{{ route('admin.dashboard') }}#stok-realtime" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-box-seam me-1"></i> Monitoring Stok
        </a>
    </div>

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
@endsection