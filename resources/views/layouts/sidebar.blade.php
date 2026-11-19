@php
    // Staff memakai navigasi section in-page di dashboard-nya, sedangkan
    // halaman lain (DSS, Master Supplier) memakai URL penuh supaya menu
    // sidebar tetap bisa diklik dari halaman mana pun.
    $isStaff = Auth::user()->role === 'staff';
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
            <a href="{{ $staffDashboardUrl }}#dashboard" data-tab="dashboard" class="nav-link {{ $activeRoute === 'dashboard' ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('staff.eis.index') }}" class="nav-link {{ request()->routeIs('staff.eis*') ? 'active' : '' }}">
                <i class="bi bi-person-lines-fill"></i>
                <span>Kinerja Saya</span>
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
            <a href="{{ $staffDashboardUrl }}#monitoring-stok" data-tab="monitoring-stok" class="nav-link {{ $activeRoute === 'monitoring' ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Monitoring Stok</span>
            </a>
            <a href="{{ $staffDashboardUrl }}#pengajuan-restock" data-tab="pengajuan-restock" class="nav-link {{ $activeRoute === 'pengajuan' ? 'active' : '' }}">
                <i class="bi bi-plus-square"></i>
                <span>Pengajuan Restock</span>
            </a>
            <a href="{{ route('staff.riwayat-stok') }}" class="nav-link {{ $activeRoute === 'riwayat' ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                <span>Riwayat Stok</span>
            </a>
        </div>
    @endif

    @if(Auth::user()->role === 'admin')
        <div class="nav-section">
            <div class="nav-section-label">ES</div>
            <a href="{{ route('admin.es') }}" class="nav-link {{ request()->routeIs('admin.es*') ? 'active' : '' }}">
                <i class="bi bi-speedometer"></i>
                <span>Dashboard Eksekutif</span>
            </a>
            <a href="{{ route('admin.eis') }}" class="nav-link {{ request()->routeIs('admin.eis*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i>
                <span>EIS Kinerja Staf</span>
            </a>
        </div>
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
        <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">
            <i class="bi bi-gear"></i>
            <span>Pengaturan</span>
        </a>
    </div>
</nav>
