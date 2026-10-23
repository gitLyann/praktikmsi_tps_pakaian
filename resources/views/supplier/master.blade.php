@extends('layouts.app', [
    'title' => 'Master Supplier',
    'sidebar' => true,
    'activeRoute' => 'supplier',
    'dashboardRoute' => 'admin.dashboard'
])

@section('content')
    @php
        $totalSupplier = $suppliers->count();
        $supplierAktif = $suppliers->filter(fn ($item) => $item->is_active)->count();
        $supplierNonaktif = $totalSupplier - $supplierAktif;
        $totalProdukDipasok = $suppliers->sum('product_suppliers_count');
        $formAction = $editing ? route('supplier.master.update', $editing) : route('supplier.master.store');
    @endphp

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h3 class="mb-1">Master Supplier</h3>
            <p class="text-muted mb-0">
                Data induk pemasok La Mamora: kontak, lokasi, kategori bahan yang dipasok,
                lead time, dan minimum order. Data ini dipakai untuk penilaian DSS dan
                form pengajuan restock.
            </p>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-person-circle me-1"></i>{{ ucfirst(Auth::user()->role) }}
        </span>
    </div>

    @if(session('success'))
        <x-ui.toast type="success" class="mb-3">{{ session('success') }}</x-ui.toast>
    @endif

    @if(session('error'))
        <x-ui.toast type="danger" class="mb-3">{{ session('error') }}</x-ui.toast>
    @endif

    @if($errors->any())
        <x-ui.toast type="danger" class="mb-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $errorMessage)
                    <li>{{ $errorMessage }}</li>
                @endforeach
            </ul>
        </x-ui.toast>
    @endif

    <div class="lm-metric-row mb-4">
        <div class="lm-metric">
            <div class="lm-metric-label">Total Pemasok</div>
            <div class="lm-metric-value">{{ $totalSupplier }}</div>
        </div>
        <div class="lm-metric">
            <div class="lm-metric-label">Aktif</div>
            <div class="lm-metric-value is-success">{{ $supplierAktif }}</div>
        </div>
        <div class="lm-metric">
            <div class="lm-metric-label">Nonaktif</div>
            <div class="lm-metric-value {{ $supplierNonaktif > 0 ? 'is-danger' : '' }}">{{ $supplierNonaktif }}</div>
        </div>
        <div class="lm-metric">
            <div class="lm-metric-label">Produk Dipasok</div>
            <div class="lm-metric-value">{{ $totalProdukDipasok }}</div>
        </div>
    </div>

    {{-- ============================ PENCARIAN & FILTER ============================ --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="lm-section-title mb-0"><i class="bi bi-search me-2"></i>Cari &amp; Filter</h5>
            <a href="{{ route('supplier.master') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('supplier.master') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="supplierSearch">Kata Kunci</label>
                    <input type="search" class="form-control" id="supplierSearch" name="q"
                        value="{{ $search }}" placeholder="Cari nama, kode, kontak, kota, atau kategori bahan">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="supplierStatus">Status</label>
                    <select class="form-select" id="supplierStatus" name="status">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i>Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================ FORM TAMBAH / UBAH ============================ --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="lm-section-title mb-0">
                <i class="bi bi-{{ $editing ? 'pencil-square' : 'plus-circle' }} me-2"></i>
                {{ $editing ? 'Ubah Data Pemasok' : 'Tambah Pemasok Baru' }}
            </h5>
            @if($editing)
                <a href="{{ route('supplier.master') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Batal Ubah
                </a>
            @endif
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $formAction }}">
                @csrf
                @if($editing)
                    @method('PUT')
                @endif

                <div class="lm-form-grid mb-3">
                    <div>
                        <label class="form-label" for="supplierName">Nama Pemasok <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplierName" name="name" required
                            value="{{ old('name', $editing->name ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="supplierCode">Kode</label>
                        <input type="text" class="form-control" id="supplierCode" name="code"
                            value="{{ old('code', $editing->code ?? '') }}"
                            placeholder="{{ $editing ? $editing->code : 'SUP-xxx (otomatis bila kosong)' }}">
                        <div class="form-text">Boleh dikosongkan, sistem membuatkan kode SUP-xxx.</div>
                    </div>
                    <div>
                        <label class="form-label" for="supplierContact">Nama Kontak</label>
                        <input type="text" class="form-control" id="supplierContact" name="contact_name"
                            value="{{ old('contact_name', $editing->contact_name ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="supplierPhone">Telepon</label>
                        <input type="text" class="form-control" id="supplierPhone" name="phone"
                            value="{{ old('phone', $editing->phone ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="supplierEmail">Email</label>
                        <input type="email" class="form-control" id="supplierEmail" name="email"
                            value="{{ old('email', $editing->email ?? '') }}">
                    </div>
                    <div>
                        <label class="form-label" for="supplierCity">Kota</label>
                        <input type="text" class="form-control" id="supplierCity" name="city"
                            value="{{ old('city', $editing->city ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="supplierAddress">Alamat</label>
                        <textarea class="form-control" id="supplierAddress" name="address" rows="2">{{ old('address', $editing->address ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="supplierCategories">Kategori Bahan / Produk yang Dipasok</label>
                        <input type="text" class="form-control" id="supplierCategories" name="supplied_categories"
                            list="supplierCategoryList"
                            value="{{ old('supplied_categories', $editing->supplied_categories ?? '') }}"
                            placeholder="Contoh: Kaos, Kemeja, Celana">
                        <datalist id="supplierCategoryList">
                            @foreach($categories as $categoryName)
                                <option value="{{ $categoryName }}"></option>
                            @endforeach
                        </datalist>
                        <div class="form-text">Pisahkan dengan koma bila lebih dari satu kategori.</div>
                    </div>
                    <div>
                        <label class="form-label" for="supplierLeadTime">Lead Time Rata-rata (hari) <span class="text-danger">*</span></label>
                        <input type="number" min="1" max="60" class="form-control" id="supplierLeadTime" name="lead_time_days" required
                            value="{{ old('lead_time_days', $editing->lead_time_days ?? 7) }}">
                    </div>
                    <div>
                        <label class="form-label" for="supplierMoq">Minimal Order (pcs) <span class="text-danger">*</span></label>
                        <input type="number" min="1" class="form-control" id="supplierMoq" name="min_order_qty" required
                            value="{{ old('min_order_qty', $editing->min_order_qty ?? 10) }}">
                    </div>
                    <div>
                        <label class="form-label" for="supplierRating">Rating (0-5)</label>
                        <input type="number" step="0.1" min="0" max="5" class="form-control" id="supplierRating" name="rating"
                            value="{{ old('rating', $editing->rating ?? 4) }}">
                    </div>
                    <div class="d-flex align-items-end">
                        <div class="form-check">
                            {{-- Hidden input dipakai supaya status nonaktif tetap terkirim
                                 ketika checkbox tidak dicentang. --}}
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="supplierActive" name="is_active" value="1"
                                @checked(old('is_active', $editing->is_active ?? true))>
                            <label class="form-check-label" for="supplierActive">Status Aktif</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="supplierNotes">Catatan</label>
                        <textarea class="form-control" id="supplierNotes" name="notes" rows="2">{{ old('notes', $editing->notes ?? '') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-{{ $editing ? 'save' : 'plus-lg' }} me-1"></i>
                    {{ $editing ? 'Simpan Perubahan' : 'Tambah Pemasok' }}
                </button>
            </form>
        </div>
    </div>

    {{-- ============================ DAFTAR PEMASOK ============================ --}}
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="lm-section-title mb-0"><i class="bi bi-truck me-2"></i>Daftar Pemasok</h5>
            <span class="lm-badge lm-badge-neutral">{{ $totalSupplier }} data</span>
        </div>
        <div class="card-body">
                @if($suppliers->isEmpty())
                    <x-ui.empty-state
                        icon="bi bi-buildings"
                        title="Belum ada data pemasok"
                        description="Tambahkan pemasok lewat form di atas atau ubah kata kunci pencarian."
                    />
                @else
                <div class="lm-table-wrap">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Kontak</th>
                                <th>Lokasi</th>
                                <th>Kategori Dipasok</th>
                                <th>Lead Time / MOQ</th>
                                <th>Produk</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suppliers as $supplier)
                                <tr>
                                    <td class="lm-score">{{ $supplier->code }}</td>
                                    <td>
                                        <strong>{{ $supplier->name }}</strong>
                                        @if($supplier->rating > 0)
                                            <div class="lm-criterion-type">Rating {{ number_format((float) $supplier->rating, 1) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $supplier->contact_name ?: '-' }}
                                        <div class="lm-criterion-type">{{ $supplier->phone ?: '-' }}</div>
                                        <div class="lm-criterion-type">{{ $supplier->email ?: '-' }}</div>
                                    </td>
                                    <td>
                                        {{ $supplier->city ?: '-' }}
                                        @if($supplier->address)
                                            <div class="lm-criterion-type">{{ $supplier->address }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @forelse($supplier->suppliedCategoryList() as $suppliedCategory)
                                            <span class="lm-badge lm-badge-info me-1">{{ $suppliedCategory }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </td>
                                    <td class="lm-score">{{ $supplier->lead_time_days }} hari</td>
                                    <td class="lm-score">MOQ {{ $supplier->min_order_qty }}</td>
                                    <td class="lm-score">{{ $supplier->product_suppliers_count }}</td>
                                    <td>
                                        @if($supplier->is_active)
                                            <span class="lm-badge lm-badge-approved">Aktif</span>
                                        @else
                                            <span class="lm-badge lm-badge-rejected">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('supplier.master', array_filter([
                                            'q' => $search !== '' ? $search : null,
                                            'status' => $status !== 'all' ? $status : null,
                                            'edit' => $supplier->id,
                                        ])) }}"
                                            class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </a>
                                        @if(Auth::user()->role === 'admin')
                                            <form method="POST" action="{{ route('supplier.master.destroy', $supplier->id) }}" class="d-inline"
                                                onsubmit="return confirm('Hapus pemasok {{ $supplier->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash me-1"></i>Hapus
                                                </button>
                                            </form>
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
@endsection
