@php
    // Staff memakai navigasi section in-page di dashboard-nya, sedangkan
    // halaman lain (DSS, Master Supplier) memakai URL penuh supaya menu
    // sidebar tetap bisa diklik dari halaman mana pun.
    $isStaff = Auth::user()->role === 'staff';
    $onStaffDashboard = $isStaff && request()->routeIs('staff.dashboard');
    $staffDashboardUrl = route('staff.dashboard');
@endphp

<div class="sidebar-header">
    <a href="{{ Auth::user()->role === 'admin' ? route('admin.dashboard') : route('staff.dashboard') }}" class="sidebar-brand d-flex align-items-center gap-2">
        @include('layouts.lamamora-logo', ['height' => 34])
        <span>La Mamora.</span>
    </a>
    <div class="d-flex align-items-center gap-2 mt-2">
        <x-ui.avatar :name="Auth::user()->name" size="sm" />
        <span class="badge ui-badge bg-primary">{{ ucfirst(Auth::user()->role ?? 'user') }}</span>
    </div>
</div>

<nav class="sidebar-nav flex-grow: 1" aria-label="Main navigation">
    <div class="nav-section">
        <div class="nav-section-label">Utama</div>
        @if($isStaff)
            {{-- Halaman dashboard staff memakai navigasi section in-page custom.
                 URL tetap ditulis penuh supaya link tetap berfungsi saat halaman ini
                 bukan dashboard (mis. halaman DSS). --}}
            <a href="{{ $staffDashboardUrl }}#dashboard" class="nav-link {{ $activeRoute === 'dashboard' ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        @else
            <a href="{{ route($dashboardRoute) }}#dashboard" class="nav-link {{ $activeRoute === 'dashboard' ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('admin.dashboard') }}#analisis-mis" class="nav-link {{ $activeRoute === 'analisis' ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Analisis Penjualan</span>
            </a>
        @endif
    </div>

    @if($isStaff)
        <div class="nav-section">
            <div class="nav-section-label">OAS & Stok</div>
            <a href="{{ $staffDashboardUrl }}#monitoring-stok" class="nav-link {{ $activeRoute === 'monitoring' ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Monitoring Stok</span>
            </a>
            <a href="{{ $staffDashboardUrl }}#pengajuan-restock" class="nav-link {{ $activeRoute === 'pengajuan' ? 'active' : '' }}">
                <i class="bi bi-plus-square"></i>
                <span>Pengajuan Restock</span>
            </a>
            <a href="{{ $staffDashboardUrl }}#riwayat-stok" class="nav-link {{ $activeRoute === 'riwayat' ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat Stok</span>
            </a>
        </div>
    @endif

    @if(Auth::user()->role === 'admin')
        <div class="nav-section">
            <div class="nav-section-label">OAS & Stok</div>
            <a href="{{ route('admin.dashboard') }}#stok-realtime" class="nav-link {{ $activeRoute === 'stok' ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Monitoring Stok Realtime</span>
            </a>
            <a href="{{ route('admin.dashboard') }}#kelola-produk" class="nav-link {{ $activeRoute === 'produk' ? 'active' : '' }}">
                <i class="bi bi-pencil-square"></i>
                <span>Kelola Produk</span>
            </a>
            <a href="{{ route('admin.dashboard') }}#persetujuan-restock" class="nav-link {{ $activeRoute === 'persetujuan' ? 'active' : '' }}">
                <i class="bi bi-check-circle"></i>
                <span>Persetujuan Restock</span>
            </a>
            <a href="{{ route('admin.dashboard') }}#riwayat-stok" class="nav-link {{ $activeRoute === 'riwayat' ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat Stok</span>
            </a>
        </div>
    @endif

    @if(Auth::user()->role === 'staff' || Auth::user()->role === 'admin')
        <div class="nav-section">
            <div class="nav-section-label">DSS</div>
            @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.dss') }}" class="nav-link {{ request()->routeIs('admin.dss*') ? 'active' : '' }}">
                    <i class="bi bi-trophy"></i>
                    <span>Analisis DSS</span>
                </a>
            @endif
            <a href="{{ route('staff.dss') }}" class="nav-link {{ request()->routeIs('staff.dss*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data"></i>
                <span>Penilaian Pemasok</span>
            </a>
        </div>
    @endif

    @if($isStaff || Auth::user()->role === 'admin')
        <div class="nav-section">
            <div class="nav-section-label">Master Data</div>
            <a href="{{ route('supplier.master') }}" class="nav-link {{ $activeRoute === 'supplier' ? 'active' : '' }}">
                <i class="bi bi-buildings"></i>
                <span>Master Supplier</span>
            </a>
        </div>
    @endif

    <div class="nav-section">
        <div class="nav-section-label">Akun</div>
        <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#profileModal">
            <i class="bi bi-person-circle"></i>
            <span>Profil Saya</span>
        </a>
        @if($isStaff && $onStaffDashboard)
            {{-- Di dashboard staff, Pengaturan sudah menjadi section in-page --}}
            <a href="{{ $staffDashboardUrl }}#pengaturan" data-bs-target="#pengaturan" class="nav-link {{ $activeRoute === 'pengaturan' ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                <span>Pengaturan</span>
            </a>
        @else
            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="bi bi-gear"></i>
                <span>Pengaturan</span>
            </a>
        @endif
    </div>
</nav>

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="profileModalLabel"><i class="bi bi-person-circle me-2"></i>Profil Saya</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle fs-1 text-secondary"></i>
                </div>
                <h6 class="mb-1">{{ Auth::user()->name }}</h6>
                <p class="text-muted mb-1">{{ Auth::user()->email }}</p>
                <span class="badge bg-primary bg-opacity-10 text-primary">{{ ucfirst(Auth::user()->role ?? 'user') }}</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@if(! $isStaff || ! $onStaffDashboard)
    {{-- Modal Pengaturan. Untuk role staff modal ini hanya dirender di luar
         dashboard staff, karena di sana section #pengaturan sudah menyediakan
         tombol logout (jumlah form logout harus tetap satu di halaman itu). --}}
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="settingsModalLabel"><i class="bi bi-gear me-2"></i>Pengaturan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Mengakhiri sesi aktif pada perangkat ini. Anda akan diarahkan kembali
                        ke halaman login dan perlu masuk ulang untuk membuka dashboard.
                    </p>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout Akun
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endif
