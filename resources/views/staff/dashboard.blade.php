@extends('layouts.app', [
    'title' => 'Dashboard Staff - OAS Restock',
    'sidebar' => true,
    'activeRoute' => request()->segment(2) ?? 'dashboard',
    'dashboardRoute' => 'staff.dashboard'
])

@section('content')
    <!-- Alert Success -->
    @if(session('success'))
        <div class="mb-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- Alert Error -->
    @if(session('error'))
        <div class="mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- Error validasi form edit produk juga ditampilkan di overlay kustom,
         alert ini sebagai cadangan ketika overlay tidak bisa dibuka kembali. -->
    @if($errors->any() && !old('_produk_id'))
        <div class="mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $errorMessage)
                        <li>{{ $errorMessage }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif


    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Dashboard Staff</h3>
            <p class="text-muted mb-0">Monitoring stok, pengajuan restock (OAS), dan riwayat keputusan manager.</p>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
        </span>
    </div>

    <div id="staffDashboard">
        {{-- ================= SECTION: DASHBOARD (RINGKASAN) ================= --}}
        <div class="dashboard-section" id="dashboard">
            <!-- Widget Statistik -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Total Produk</div>
                                <div class="h4 mb-0 fw-bold">{{ $totalProduk }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-exclamation-triangle fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Stok Kritis</div>
                                <div class="h4 mb-0 fw-bold">{{ $stokKritis }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                <i class="bi bi-hourglass-split fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase">Restock Pending</div>
                                <div class="h4 mb-0 fw-bold">{{ $restockPending }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Stok Produk (maks 5 produk) -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Ringkasan Stok Produk</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($topProducts as $product)
                            <div class="col-6 col-md-4 mb-3">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="card-body d-flex flex-column">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary align-self-start mb-2">
                                            {{ $product->category->name ?? 'Tanpa Kategori' }}
                                        </span>
                                        <h6 class="card-title fw-bold">{{ $product->name }}</h6>
                                        <p class="card-text small text-muted mb-1">Varian: {{ $product->type ?? '-' }} / {{ $product->size ?? '-' }} / {{ $product->color ?? '-' }}</p>
                                        <p class="card-text small text-muted mb-1">Harga: Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                                        <p class="card-text small text-muted mb-2">Sisa Stok: {{ $product->stock }} pcs</p>
                                        @if($product->stock == 0)
                                            <span class="badge bg-danger align-self-start">Stok Habis</span>
                                        @elseif($product->stock <= $criticalThreshold)
                                            <span class="badge bg-warning text-dark align-self-start">Stok Kritis</span>
                                        @else
                                            <span class="badge bg-success align-self-start">Stok Aman</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center mb-0">Belum ada produk yang tersedia.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Ringkasan Pengajuan Terbaru -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light fw-bold">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Ringkasan Pengajuan Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Produk</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRestocks as $req)
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

        {{-- ================= SECTION: MONITORING STOK ================= --}}
        <div class="dashboard-section d-none" id="monitoring-stok">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Detail Produk & Monitoring Stok</h5>
                    <span class="badge bg-white text-primary">{{ $totalProduk }} produk</span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12 col-md-6 col-lg-5">
                            <label for="search-product" class="form-label small text-muted">Search / Filter Produk</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="search" id="search-product" class="form-control"
                                    placeholder="Cari nama / ukuran / warna produk..." autocomplete="off">
                            </div>
                            <div class="form-text" id="search-product-info">Menampilkan {{ $totalProduk }} produk.</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0" id="productTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Color</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr data-product-row data-search="{{ strtolower(trim($product->name . ' ' . ($product->category->name ?? '') . ' ' . ($product->type ?? '') . ' ' . ($product->size ?? '') . ' ' . ($product->color ?? ''))) }}">
                                        <td>{{ $product->id }}</td>
                                        <td class="fw-semibold">{{ $product->name }}</td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $product->category->name ?? '-' }}</span></td>
                                        <td>{{ $product->type ?? '-' }}</td>
                                        <td>{{ $product->size ?? '-' }}</td>
                                        <td>{{ $product->color ?? '-' }}</td>
                                        <td class="text-end">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                        <td class="text-center fw-semibold">{{ $product->stock }}</td>
                                        <td class="text-center">
                                            @if($product->stock == 0)
                                                <span class="badge bg-danger">Stok Habis</span>
                                            @elseif($product->stock <= $criticalThreshold)
                                                <span class="badge bg-warning text-dark">Stok Kritis</span>
                                            @else
                                                <span class="badge bg-success">Stok Aman</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-nowrap">
                                            {{-- Preview read-only produk. Tombol (bukan link) supaya staff
                                                 tetap berada di dashboard, tidak pindah ke halaman
                                                 publik /toko/produk/{id}. Data diambil dari atribut
                                                 data-* agar modal cukup diisi oleh JS tanpa request. --}}
                                            <button type="button" class="btn btn-outline-info btn-sm btn-lihat-produk"
                                                data-lihat-produk="{{ $product->id }}"
                                                data-nama="{{ $product->name }}"
                                                data-kategori="{{ $product->category->name ?? '-' }}"
                                                data-type="{{ $product->type ?? '-' }}"
                                                data-size="{{ $product->size ?? '-' }}"
                                                data-color="{{ $product->color ?? '-' }}"
                                                data-harga="Rp {{ number_format($product->price, 0, ',', '.') }}"
                                                data-stok="{{ (int) $product->stock }}"
                                                data-deskripsi="{{ $product->description ?? '' }}"
                                                data-gambar="{{ $product->image_url ?? '' }}"
                                                title="Lihat detail produk">
                                                <i class="bi bi-eye me-1"></i>Lihat
                                            </button>

                                            {{-- Data form edit disimpan di atribut data-* agar overlay
                                                 cukup diisi oleh JS tanpa request ke server lagi --}}
                                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-produk"
                                                    data-edit-produk="{{ $product->id }}"
                                                    data-nama="{{ $product->name }}"
                                                    data-kategori="{{ $product->category->name ?? '' }}"
                                                    data-type="{{ $product->type ?? '' }}"
                                                    data-size="{{ $product->size ?? '' }}"
                                                    data-color="{{ $product->color ?? '' }}"
                                                    data-harga="{{ (int) $product->price }}"
                                                    data-deskripsi="{{ $product->description ?? '' }}"
                                                    data-gambar="{{ $product->image_url ?? '' }}">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="productEmptyRow">
                                        <td colspan="10" class="text-center text-muted">Belum ada produk yang tersedia.</td>
                                    </tr>
                                @endforelse
                                <tr id="productNoMatchRow" class="d-none">
                                    <td colspan="10" class="text-center text-muted">Produk tidak ditemukan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SECTION: PENGAJUAN RESTOCK ================= --}}
        <div class="dashboard-section d-none" id="pengajuan-restock">
            <div class="row g-4">
                <!-- Form Pengajuan Restock (OAS) -->
                <div class="col-12 col-lg-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark fw-bold">
                            <i class="bi bi-plus-square me-2"></i>Form Pengajuan Restock Barang (OAS)
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.restock.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="product_id" class="form-label">Pilih Produk</label>
                                    <select name="product_id" id="product_id" class="form-select" required>
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" @selected(request()->integer('produk') === $product->id)>{{ $product->name }} (Stok Saat Ini: {{ $product->stock }})</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Belum ada produknya? Tambahkan master produk baru lewat card di bawah halaman ini.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="jumlah_restock" class="form-label">Jumlah Restock (Qty)</label>
                                    <input type="number" name="jumlah_restock" id="jumlah_restock" class="form-control" min="1" placeholder="Masukkan jumlah" required>
                                </div>
                                <button type="submit" class="btn btn-warning w-100 fw-bold"><i class="bi bi-send me-2"></i>Kirim Pengajuan ke Manager</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Daftar Pengajuan Menunggu Keputusan (Pending Only) -->
                <div class="col-12 col-lg-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-info text-white fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-hourglass-split me-2"></i>Daftar Pengajuan Menunggu Keputusan (Pending Only)</span>
                            <span class="badge bg-white text-info">{{ $pendingRestocks->count() }}</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Produk</th>
                                            <th>Jumlah</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pendingRestocks as $req)
                                            <tr>
                                                <td>{{ $req->id }}</td>
                                                <td>
                                                    <span class="fw-semibold d-block">{{ $req->product->name ?? 'Produk N/A' }}</span>
                                                    <small class="text-muted">Diajukan oleh {{ $req->employee->name ?? 'Staff' }}</small>
                                                </td>
                                                <td>{{ $req->jumlah_restock }} pcs</td>
                                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Tidak ada pengajuan yang menunggu keputusan.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tambah Master Produk Baru: form inline, berada di bawah Form & Tabel Pengajuan Restock --}}
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-box-seam me-2"></i>Tambah Master Produk Baru
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Gagal menyimpan produk.</strong> Periksa kembali isian yang ditandai merah.
                        </div>
                    @endif

                    @php
                        // Bila kategori dari input ulang tidak ada di daftar, perlakukan sebagai kategori baru
                        $oldCategory = old('category');
                        $isNewCategory = (bool) ($oldCategory && ! $categories->contains(fn ($item) => $item->name === $oldCategory));
                        $productTypes = ['Atasan', 'Bawahan', 'Aksesoris'];
                        $oldType = old('type');
                        $isNewType = (bool) ($oldType && ! in_array($oldType, $productTypes, true));
                    @endphp

                    <form action="{{ route('staff.products.store') }}" method="POST" id="formTambahProduk" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="category-select" class="form-label">Category <span class="text-danger">*</span></label>
                                <select id="category-select" class="form-select mb-2">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $categoryOption)
                                        <option value="{{ $categoryOption->name }}" @selected(! $isNewCategory && old('category') === $categoryOption->name)>{{ $categoryOption->name }}</option>
                                    @endforeach
                                    <option value="__baru__" @selected($isNewCategory)>+ Tambah Kategori Baru...</option>
                                </select>
                                <input type="text" name="category" id="category-input" class="form-control @unless($isNewCategory) d-none @endunless"
                                    maxlength="50" placeholder="Ketik nama kategori baru..." value="{{ old('category') }}" required>
                                <div class="form-text">Kategori yang belum terdaftar akan dibuat otomatis saat produk disimpan.</div>
                                @error('category')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="type-select" class="form-label">Type</label>
                                <select id="type-select" class="form-select mb-2">
                                    <option value="">-- Pilih Type --</option>
                                    @foreach($productTypes as $typeOption)
                                        <option value="{{ $typeOption }}" @selected(! $isNewType && old('type') === $typeOption)>{{ $typeOption }}</option>
                                    @endforeach
                                    <option value="__baru__" @selected($isNewType)>+ Tambah Type Baru...</option>
                                </select>
                                <input type="text" name="type" id="type-input" class="form-control @unless($isNewType) d-none @endunless"
                                    maxlength="50" placeholder="Ketik type baru..." value="{{ old('type') }}">
                                <div class="form-text">Type bersifat opsional, boleh dikosongkan.</div>
                                @error('type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" maxlength="100"
                                    placeholder="Contoh: Kemeja Flanel Pria" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" name="color" id="color" class="form-control" maxlength="30"
                                    placeholder="Contoh: Merah" value="{{ old('color') }}">
                                @error('color')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="image" class="form-label">Foto Produk</label>
                                <input type="file" name="image" id="image" class="form-control"
                                    accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">Opsional. Format JPG/PNG/WEBP maksimal 2 MB. Disimpan di <code>public/images/produk</code>.</div>
                                @error('image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="size" class="form-label">Size</label>
                                <input type="text" name="size" id="size" class="form-control" maxlength="10"
                                    placeholder="M" value="{{ old('size') }}">
                                @error('size')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="price" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" name="price" id="price" class="form-control" min="0" step="1"
                                    placeholder="0" value="{{ old('price') }}" required>
                                @error('price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Deskripsi Produk</label>
                                <textarea name="description" id="description" rows="3" class="form-control"
                                    maxlength="2000" placeholder="Contoh: Bahan katun combed 30s, adem dan tidak mudah kusut.">{{ old('description') }}</textarea>
                                <div class="form-text">Opsional. Maksimal 2000 karakter. Deskripsi ini tampil di halaman detail produk.</div>
                                @error('description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Simpan Produk
                                </button>
                                <span class="form-text mb-0 text-muted fst-italic small">
                                    *Catatan: Produk baru akan didaftarkan dengan stok 0. Untuk mengisi stok fisik, silakan ajukan restock setelah produk disimpan.*
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ================= SECTION: RIYATAT STOK (AUDIT TRAIL) ================= --}}
        <div class="dashboard-section d-none" id="riwayat-stok">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Aktivitas Stok</h5>
                    <div>
                        <span class="badge bg-info">Produk Baru: {{ $riwayatStok->where('tipe', 'Produk Baru')->count() }}</span>
                        <span class="badge bg-warning text-dark">Pending: {{ $allRestocks->where('status', 'pending')->count() }}</span>
                        <span class="badge bg-success">Approved: {{ $allRestocks->where('status', 'approved')->count() }}</span>
                        <span class="badge bg-danger">Rejected: {{ $allRestocks->where('status', 'rejected')->count() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Menggabungkan aktivitas pendaftaran master produk baru dan pengajuan restock, diurutkan dari yang terbaru.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tipe Aktivitas</th>
                                    <th>Produk</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($riwayatStok as $row)
                                    <tr>
                                        <td class="text-nowrap">{{ $row['tanggal']?->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td>
                                            @if($row['tipe'] === 'Produk Baru')
                                                <span class="badge bg-info text-dark">Produk Baru</span>
                                            @else
                                                <span class="badge bg-secondary">Pengajuan Restock</span>
                                            @endif
                                        </td>
                                        <td class="fw-semibold">{{ $row['produk'] }}</td>
                                        <td>{{ $row['jumlah'] !== null ? $row['jumlah'] . ' pcs' : '—' }}</td>
                                        <td>
                                            @if($row['status'] === 'terdaftar')
                                                <span class="badge bg-info text-dark">Terdaftar</span>
                                            @elseif($row['status'] === 'pending')
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @elseif($row['status'] === 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @else
                                                <span class="badge bg-danger">Rejected</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $row['keterangan'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada aktivitas stok.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= SECTION: PENGATURAN ================= --}}
        <div class="dashboard-section d-none" id="pengaturan">
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-secondary text-white fw-bold">
                            <i class="bi bi-person-circle me-2"></i>Informasi Akun
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted">Nama</span>
                                    <span class="fw-semibold">{{ Auth::user()->name }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted">Email</span>
                                    <span class="fw-semibold">{{ Auth::user()->email }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted">Role</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ ucfirst(Auth::user()->role ?? 'user') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-danger text-white fw-bold">
                            <i class="bi bi-shield-lock me-2"></i>Pengaturan & Keamanan Akun
                        </div>
                        <div class="card-body d-flex flex-column">
                            <p class="text-muted small">
                                Mengakhiri sesi aktif pada perangkat ini. Anda akan diarahkan kembali ke halaman login
                                dan perlu melakukan login ulang untuk mengakses dashboard staff.
                            </p>
                            <form action="{{ route('logout') }}" method="POST" class="mt-auto">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout Akun
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= OVERLAY EDIT PRODUK =================
         Overlay ini sengaja diletakkan DI LUAR wrapper #staffDashboard.
         Section dashboard memakai kelas d-none (display:none !important) untuk
         berpindah tab, dan elemen di dalamnya akan ikut tersembunyi walau
         overlay-nya sendiri sudah .show. --}}
    <div class="produk-overlay" id="editProdukOverlay" role="dialog" aria-modal="true" aria-labelledby="editProdukTitle" hidden>
        <div class="produk-overlay-box">
            <div class="produk-overlay-head">
                <h5 class="mb-0" id="editProdukTitle">
                    <i class="bi bi-pencil-square me-2"></i>Edit Produk
                </h5>
                <button type="button" class="btn-close" id="editProdukClose" aria-label="Tutup"></button>
            </div>

            <form action="" method="POST" id="editProdukForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Disembunyikan dari validasi, hanya dipakai JS untuk membuka
                     kembali overlay yang benar setelah validasi ditolak --}}
                <input type="hidden" name="_produk_id" id="editProdukId" value="">

                <div class="produk-overlay-body">
                    <div id="editProdukError" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="editNama" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editNama" class="form-control" maxlength="100" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editKategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="category" id="editKategori" class="form-control" maxlength="50" list="editKategoriList" required>
                            {{-- Ketik kategori bebas tetap bisa, kategori baru dibuat otomatis oleh backend --}}
                            <datalist id="editKategoriList">
                                @foreach($categories as $category)
                                    <option value="{{ $category->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editType" class="form-label">Type</label>
                            <input type="text" name="type" id="editType" class="form-control" maxlength="50">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editSize" class="form-label">Size</label>
                            <input type="text" name="size" id="editSize" class="form-control" maxlength="10">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editColor" class="form-label">Color</label>
                            <input type="text" name="color" id="editColor" class="form-control" maxlength="30">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editHarga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="editHarga" class="form-control" min="0" step="1" required>
                        </div>

                        <div class="col-12">
                            <label for="editDeskripsi" class="form-label">Deskripsi Produk</label>
                            <textarea name="description" id="editDeskripsi" rows="4" class="form-control" maxlength="2000"
                                placeholder="Bahan, ukuran, keistimewaan, atau catatan lain tentang produk ini."></textarea>
                            <div class="form-text">Maksimal 2000 karakter. Tampil di halaman detail produk pelanggan.</div>
                        </div>

                        <div class="col-12">
                            <label for="editImage" class="form-label">Ganti Foto Produk</label>
                            <input type="file" name="image" id="editImage" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Kosongkan bila tidak ingin mengganti foto. Format JPG/PNG/WEBP maksimal 2 MB.</div>

                            <div class="d-flex align-items-center gap-2 mt-2">
                                <img id="editImagePreview" src="" alt="Foto produk saat ini" class="produk-preview d-none">
                                <span id="editImageInfo" class="text-muted small">Produk ini belum memiliki foto.</span>
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="editRemoveImage" name="remove_image">
                                <label class="form-check-label" for="editRemoveImage">Hapus foto produk ini</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Stok tidak dapat diubah dari form ini. Perubahan stok hanya dilakukan lewat pengajuan restock.
                    </div>
                </div>

                <div class="produk-overlay-foot">
                    <button type="button" class="btn btn-outline-secondary" id="editProdukBatal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= OVERLAY LIHAT PRODUK (READ-ONLY) =================
         Sengaja tidak memakai <form> sama sekali: seluruh isi hanya teks baca-saja
         (Foto, Nama, Kategori, Type, Size, Color, Harga, Stok, Deskripsi) dan satu
         tombol Tutup, sehingga staff tidak bisa mengedit apa pun dari sini. --}}
    <div class="produk-overlay" id="lihatProdukOverlay" role="dialog" aria-modal="true" aria-labelledby="lihatProdukTitle" hidden>
        <div class="produk-overlay-box">
            <div class="produk-overlay-head">
                <h5 class="mb-0" id="lihatProdukTitle">
                    <i class="bi bi-eye me-2"></i>Lihat Produk
                </h5>
            </div>

            <div class="produk-overlay-body">
                <div class="produk-detail-media">
                    <img id="lihatProdukFoto" class="produk-detail-photo d-none" alt="Foto produk">
                    <div id="lihatProdukTanpaFoto" class="produk-detail-fallback">
                        <i class="bi bi-image me-2"></i>Produk ini belum punya foto
                    </div>
                </div>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Nama Produk</dt>
                    <dd class="col-sm-8" id="lihatProdukNama">-</dd>

                    <dt class="col-sm-4">Kategori</dt>
                    <dd class="col-sm-8" id="lihatProdukKategori">-</dd>

                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8" id="lihatProdukType">-</dd>

                    <dt class="col-sm-4">Size</dt>
                    <dd class="col-sm-8" id="lihatProdukSize">-</dd>

                    <dt class="col-sm-4">Color</dt>
                    <dd class="col-sm-8" id="lihatProdukColor">-</dd>

                    <dt class="col-sm-4">Harga</dt>
                    <dd class="col-sm-8 fw-semibold" id="lihatProdukHarga">-</dd>

                    <dt class="col-sm-4">Stok</dt>
                    <dd class="col-sm-8" id="lihatProdukStok">-</dd>
                </dl>

                <hr class="my-3">

                <h6 class="mb-2">Deskripsi Produk</h6>
                <div id="lihatProdukDeskripsi" class="produk-detail-deskripsi">Belum ada deskripsi untuk produk ini.</div>

                <div class="alert alert-secondary mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Halaman ini hanya menampilkan data. Untuk mengubah produk gunakan tombol pensil di
                    baris tabel, dan penambahan stok dilakukan lewat pengajuan restock.
                </div>
            </div>

            <div class="produk-overlay-foot">
                <button type="button" class="btn btn-outline-secondary" id="lihatProdukTutup">Tutup</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const staffDashboard = document.getElementById('staffDashboard');
            if (!staffDashboard) return;

            const sections = Array.from(staffDashboard.querySelectorAll('.dashboard-section'));
            const navLinks = Array.from(document.querySelectorAll('.sidebar-nav .nav-link[data-bs-target]'));
            const defaultSectionId = 'dashboard';

            // Sembunyikan section yang sedang aktif, tampilkan section yang diklik (tanpa reload)
            const activateSection = id => {
                if (!sections.some(section => section.id === id)) return;

                sections.forEach(section => {
                    section.classList.toggle('d-none', section.id !== id);
                });

                navLinks.forEach(link => {
                    link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
                });

                if (window.location.hash !== `#${id}`) {
                    history.replaceState(null, '', `#${id}`);
                }
            };

            // Sidebar staff -> pindah section, lalu tutup sidebar di layar kecil
            navLinks.forEach(link => {
                link.addEventListener('click', event => {
                    event.preventDefault();
                    activateSection(link.getAttribute('href').slice(1));

                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    if (sidebar && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                        if (overlay) overlay.classList.remove('show');
                    }
                });
            });

            // Buka section sesuai location.hash saat halaman dimuat / hash berubah
            const sectionFromHash = () => {
                const id = window.location.hash.slice(1);
                activateSection(sections.some(section => section.id === id) ? id : defaultSectionId);
            };

            sectionFromHash();
            window.addEventListener('hashchange', sectionFromHash);

            // Notifikasi sukses/error berada di paling atas halaman, sedangkan form produk kini
            // berada di bawah tabel Pengajuan Restock, jadi naikkan ke atas agar pesan terlihat.
            @if(session('success') || $errors->any())
                window.scrollTo({ top: 0, behavior: 'smooth' });
            @endif

            // Select bertingkat untuk form Tambah Master Produk Baru:
            // <select> hanya jadi kendali UI, sedangkan <input> di bawahnya yang menyimpan nilainya.
            // Memilih "__baru__" memunculkan input teks untuk mengetik kategori/type yang belum ada.
            const setupSelectBertingkat = (selectId, inputId) => {
                const select = document.getElementById(selectId);
                const input = document.getElementById(inputId);
                if (!select || !input) return;

                select.addEventListener('change', () => {
                    const modeBaru = select.value === '__baru__';
                    input.classList.toggle('d-none', !modeBaru);
                    input.value = modeBaru ? '' : select.value;
                    if (modeBaru) input.focus();
                });
            };

            setupSelectBertingkat('category-select', 'category-input');
            setupSelectBertingkat('type-select', 'type-input');

            // Search / filter tabel produk (client-side, tanpa reload)
            const searchInput = document.getElementById('search-product');
            const searchInfo = document.getElementById('search-product-info');
            const noMatchRow = document.getElementById('productNoMatchRow');
            const productRows = Array.from(document.querySelectorAll('#productTable [data-product-row]'));

            if (searchInput && productRows.length) {
                searchInput.addEventListener('input', () => {
                    const keyword = searchInput.value.trim().toLowerCase();
                    let visible = 0;

                    productRows.forEach(row => {
                        const match = !keyword || (row.dataset.search || '').includes(keyword);
                        row.classList.toggle('d-none', !match);
                        if (match) visible++;
                    });

                    if (noMatchRow) noMatchRow.classList.toggle('d-none', visible !== 0);
                    if (searchInfo) {
                        searchInfo.textContent = keyword
                            ? `Menampilkan ${visible} dari ${productRows.length} produk.`
                            : `Menampilkan ${productRows.length} produk.`;
                    }
                });
            }

            /* ================= OVERLAY EDIT PRODUK =================
               Form edit memakai overlay kustom, bukan Bootstrap Modal, supaya
               tetap terlihat meski section Monitoring Stok sedang memakai d-none. */
            const editOverlay = document.getElementById('editProdukOverlay');
            const editForm = document.getElementById('editProdukForm');
            const editFields = {
                nama: document.getElementById('editNama'),
                kategori: document.getElementById('editKategori'),
                type: document.getElementById('editType'),
                size: document.getElementById('editSize'),
                color: document.getElementById('editColor'),
                harga: document.getElementById('editHarga'),
                deskripsi: document.getElementById('editDeskripsi'),
                image: document.getElementById('editImage'),
                removeImage: document.getElementById('editRemoveImage'),
                produkId: document.getElementById('editProdukId'),
                preview: document.getElementById('editImagePreview'),
                info: document.getElementById('editImageInfo'),
                error: document.getElementById('editProdukError'),
            };
            const EDIT_URL = @json(route('staff.products.update', ['product' => 0]));

            const setPreview = (src) => {
                if (src) {
                    editFields.preview.src = src;
                    editFields.preview.classList.remove('d-none');
                    editFields.info.classList.add('d-none');
                } else {
                    editFields.preview.removeAttribute('src');
                    editFields.preview.classList.add('d-none');
                    editFields.info.classList.remove('d-none');
                }
            };

            const openEditOverlay = (btn) => {
                editForm.action = EDIT_URL + btn.dataset.editProduk;
                editFields.nama.value = btn.dataset.nama || '';
                editFields.kategori.value = btn.dataset.kategori || '';
                editFields.type.value = btn.dataset.type || '';
                editFields.size.value = btn.dataset.size || '';
                editFields.color.value = btn.dataset.color || '';
                editFields.harga.value = btn.dataset.harga || '';
                editFields.deskripsi.value = btn.dataset.deskripsi || '';
                editFields.image.value = '';
                editFields.removeImage.checked = false;
                editFields.image.disabled = false;
                editFields.produkId.value = btn.dataset.editProduk || '';
                editFields.error.classList.add('d-none');
                setPreview(btn.dataset.gambar || '');

                editOverlay.hidden = false;
                document.body.style.overflow = 'hidden';
                editFields.nama.focus();
            };

            const closeEditOverlay = () => {
                editOverlay.hidden = true;
                document.body.style.overflow = '';
            };

            if (editOverlay && editForm) {
                document.querySelectorAll('.btn-edit-produk').forEach(btn => {
                    btn.addEventListener('click', () => openEditOverlay(btn));
                });

                document.getElementById('editProdukClose').addEventListener('click', closeEditOverlay);
                document.getElementById('editProdukBatal').addEventListener('click', closeEditOverlay);
                editOverlay.addEventListener('click', (e) => { if (e.target === editOverlay) closeEditOverlay(); });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !editOverlay.hidden) closeEditOverlay();
                });

                // Mencentang "hapus foto" langsung menyembunyikan pratinjau dan
                // menonaktifkan input file, supaya tidak ada dua aksi yang bertabrakan.
                editFields.removeImage.addEventListener('change', () => {
                    setPreview('');
                    editFields.image.disabled = editFields.removeImage.checked;
                });
            }

            // Bila validasi update ditolak, buka kembali overlay dengan nilai
            // yang tadi sudah diketik supaya staff tidak perlu mengetik ulang.
            @if($errors->any() && old('_produk_id'))
                (function () {
                    const btn = document.querySelector('[data-edit-produk="{{ old('_produk_id') }}"]');
                    if (!btn) return;

                    openEditOverlay(btn);

                    // Nilai old() mengalahkan nilai default dari baris tabel,
                    // karena itu yang terakhir diketik staff sebelum submit.
                    editFields.nama.value = @json(old('name'));
                    editFields.kategori.value = @json(old('category'));
                    editFields.type.value = @json(old('type'));
                    editFields.size.value = @json(old('size'));
                    editFields.color.value = @json(old('color'));
                    editFields.harga.value = @json(old('price'));
                    editFields.deskripsi.value = @json(old('description'));

                    editFields.error.textContent = @json($errors->first());
                    editFields.error.classList.remove('d-none');
                })();
            @endif
        })();

        /* ================= OVERLAY LIHAT PRODUK (READ-ONLY) =================
           Dua IIFE terpisah supaya overlay ini tetap jalan walau markup tabel
           produk tidak ada (mis. staff belum punya produk). */
        (function () {
            const lihatOverlay = document.getElementById('lihatProdukOverlay');
            const editOverlay = document.getElementById('editProdukOverlay');
            if (!lihatOverlay) return;

            const lihatFields = {
                nama: document.getElementById('lihatProdukNama'),
                kategori: document.getElementById('lihatProdukKategori'),
                type: document.getElementById('lihatProdukType'),
                size: document.getElementById('lihatProdukSize'),
                color: document.getElementById('lihatProdukColor'),
                harga: document.getElementById('lihatProdukHarga'),
                stok: document.getElementById('lihatProdukStok'),
                deskripsi: document.getElementById('lihatProdukDeskripsi'),
                foto: document.getElementById('lihatProdukFoto'),
                tanpaFoto: document.getElementById('lihatProdukTanpaFoto'),
            };
            const KOSONG = '-';
            const DESKRIPSI_KOSONG = 'Belum ada deskripsi untuk produk ini.';
            let triggerTerakhir = null;

            // Isi memakai textContent supaya nama/deskripsi yang mengandung
            // tanda kutip atau tag tidak di-parse sebagai HTML.
            const setTeks = (el, nilai, fallback) => {
                const isi = (nilai || '').trim();
                el.textContent = isi && isi !== KOSONG ? isi : fallback;
                el.classList.toggle('produk-detail-kosong', !isi || isi === KOSONG);
            };

            const setFoto = (src) => {
                if (src) {
                    lihatFields.foto.src = src;
                    lihatFields.foto.classList.remove('d-none');
                    lihatFields.tanpaFoto.classList.add('d-none');
                } else {
                    lihatFields.foto.removeAttribute('src');
                    lihatFields.foto.classList.add('d-none');
                    lihatFields.tanpaFoto.classList.remove('d-none');
                }
            };

            const openLihatOverlay = (btn) => {
                setTeks(lihatFields.nama, btn.dataset.nama, KOSONG);
                setTeks(lihatFields.kategori, btn.dataset.kategori, KOSONG);
                setTeks(lihatFields.type, btn.dataset.type, KOSONG);
                setTeks(lihatFields.size, btn.dataset.size, KOSONG);
                setTeks(lihatFields.color, btn.dataset.color, KOSONG);
                setTeks(lihatFields.harga, btn.dataset.harga, KOSONG);
                setTeks(lihatFields.stok, btn.dataset.stok, KOSONG);
                setTeks(lihatFields.deskripsi, btn.dataset.deskripsi, DESKRIPSI_KOSONG);
                setFoto(btn.dataset.gambar || '');

                // Jaring pengaman: jangan sampai dua overlay terbuka bersamaan
                // karena keduanya mengunci scroll <body>.
                if (editOverlay && !editOverlay.hidden) {
                    editOverlay.hidden = true;
                }

                triggerTerakhir = btn;
                lihatOverlay.hidden = false;
                document.body.style.overflow = 'hidden';
                document.getElementById('lihatProdukTutup').focus();
            };

            const closeLihatOverlay = () => {
                lihatOverlay.hidden = true;
                document.body.style.overflow = '';
                if (triggerTerakhir) triggerTerakhir.focus();
                triggerTerakhir = null;
            };

            document.querySelectorAll('.btn-lihat-produk').forEach(btn => {
                btn.addEventListener('click', () => openLihatOverlay(btn));
            });

            document.getElementById('lihatProdukTutup').addEventListener('click', closeLihatOverlay);
            lihatOverlay.addEventListener('click', (e) => { if (e.target === lihatOverlay) closeLihatOverlay(); });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !lihatOverlay.hidden) closeLihatOverlay();
            });
        })();
    </script>
@endpush
