@extends('layouts.app', [
    'title' => 'Pengaturan',
    'sidebar' => true,
    'activeRoute' => 'pengaturan',
    'dashboardRoute' => Auth::user()->role === 'admin' ? 'admin.dashboard' : 'staff.dashboard',
])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Pengaturan</h3>
            <p class="text-muted mb-0">Informasi akun dan keamanan sesi Anda.</p>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-person-circle me-1"></i>{{ ucfirst($user->role ?? 'user') }}
        </span>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <x-ui.card title="Informasi Akun" icon="bi bi-person-circle">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <x-ui.avatar :name="$user->name" size="md" />
                    <div class="min-w-0">
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="text-muted small">{{ $user->email }}</div>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Nama</span>
                        <span class="fw-semibold">{{ $user->name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Email</span>
                        <span class="fw-semibold">{{ $user->email }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Role</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary">{{ ucfirst($user->role ?? 'user') }}</span>
                    </li>
                </ul>
            </x-ui.card>
        </div>

        <div class="col-12 col-lg-6">
            <x-ui.card title="Pengaturan & Keamanan Akun" icon="bi bi-shield-lock">
                <p class="text-muted small">
                    Mengakhiri sesi aktif pada perangkat ini. Anda akan diarahkan kembali ke halaman utama
                    dan perlu melakukan login ulang untuk mengakses dashboard.
                </p>
                <form action="{{ route('logout') }}" method="POST" class="mt-auto">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout Akun
                    </button>
                </form>
            </x-ui.card>
        </div>
    </div>
@endsection
