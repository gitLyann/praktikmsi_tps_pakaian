@extends('layouts.app', [
    'title' => 'Riwayat Stok - Staff',
    'sidebar' => true,
    'activeRoute' => 'riwayat',
    'dashboardRoute' => 'staff.dashboard'
])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Riwayat Stok</h3>
            <p class="text-muted mb-0">Audit trail pengajuan restock. Staff dapat melihat/mencari/memfilter; aksi ubah/hapus hanya admin.</p>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
        </span>
    </div>

    <form method="GET" action="{{ route('staff.riwayat-stok') }}" class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="q" class="form-label small text-muted mb-1">Cari</label>
                    <input type="search" id="q" name="q" value="{{ $search ?? '' }}" class="form-control" placeholder="Produk, pengaju, atau catatan...">
                </div>
                <div class="col-12 col-md-3">
                    <label for="status" class="form-label small text-muted mb-1">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="all" @selected(($status ?? 'all') === 'all')>Semua</option>
                        <option value="pending" @selected(($status ?? '') === 'pending')>Pending</option>
                        <option value="approved" @selected(($status ?? '') === 'approved')>Approved</option>
                        <option value="rejected" @selected(($status ?? '') === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="{{ route('staff.riwayat-stok') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>

    @include('partials.riwayat-stok-admin', ['auditTrail' => $auditTrail, 'canMutate' => false])
@endsection
