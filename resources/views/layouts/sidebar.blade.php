<div class="sidebar-header">
    <a href="#" class="sidebar-brand d-flex align-items-center gap-2">
        <i class="bi bi-shop text-primary fs-3"></i>
        <span>TPS Pakaian</span>
    </a>
    <span class="badge bg-primary bg-opacity-10 text-primary ms-3 mt-2 d-inline-block">
        {{ ucfirst(Auth::user()->role ?? 'user') }}
    </span>
</div>

<nav class="sidebar-nav flex-grow-1" aria-label="Main navigation">
    <div class="nav-section">
        <div class="nav-section-label">Utama</div>
        @if(Auth::user()->role === 'staff')
            {{-- Halaman dashboard staff memakai tab Bootstrap, klik menu = pindah tab tanpa reload --}}
            <a href="#dashboard" data-bs-target="#dashboard" class="nav-link {{ $activeRoute === 'dashboard' ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        @else
            <a href="{{ route($dashboardRoute) }}" class="nav-link {{ $activeRoute === 'dashboard' ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        @endif
    </div>

    @if(Auth::user()->role === 'staff')
        <div class="nav-section">
            <div class="nav-section-label">OAS & Stok</div>
            <a href="#monitoring-stok" data-bs-target="#monitoring-stok" class="nav-link {{ $activeRoute === 'monitoring' ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Monitoring Stok</span>
            </a>
            <a href="#pengajuan-restock" data-bs-target="#pengajuan-restock" class="nav-link {{ $activeRoute === 'pengajuan' ? 'active' : '' }}">
                <i class="bi bi-plus-square"></i>
                <span>Pengajuan Restock</span>
            </a>
            <a href="#riwayat-stok" data-bs-target="#riwayat-stok" class="nav-link {{ $activeRoute === 'riwayat' ? 'active' : '' }}">
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

    <div class="nav-section">
        <div class="nav-section-label">Akun</div>
        <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#profileModal">
            <i class="bi bi-person-circle"></i>
            <span>Profil Saya</span>
        </a>
        @if(Auth::user()->role === 'staff')
            <a href="#pengaturan" data-bs-target="#pengaturan" class="nav-link {{ $activeRoute === 'pengaturan' ? 'active' : '' }}">
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

<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="settingsModalLabel"><i class="bi bi-gear me-2"></i>Pengaturan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Fitur pengaturan akan segera hadir.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>