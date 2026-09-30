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
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- Notifikasi Error -->
    @if(session('error'))
        <div class="mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="mb-0"><i class="bi bi-check-circle me-2"></i>Dashboard Manager</h3>
        <a href="{{ route('admin.dashboard') }}#kelola-produk" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-box-seam me-1"></i> Kelola Produk
        </a>
    </div>

    {{-- ================= KELOLA PRODUK (KMS) =================
         Form "Tambah Master Produk" sengaja disembunyikan: halaman Manager fokus
         pada RUD (lihat, ubah, hapus) + Approval OAS. Penambahan master produk
         dilakukan lewat dashboard Staff. --}}
    <div id="kelola-produk" class="mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Daftar Master Produk</h5>
                <span class="badge bg-light text-dark">{{ $products->count() }} produk</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Foto</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        @if($product->image)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                                style="width:48px;height:48px;object-fit:cover;border-radius:8px">
                                        @else
                                            <span class="text-muted small">Tanpa foto</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $product->name }}
                                        @if($product->description)
                                            <div class="text-muted small fw-normal">
                                                {{ Str::limit($product->description, 70) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $product->category->name ?? '-' }}</span></td>
                                    <td>{{ $product->type ?? '-' }}</td>
                                    <td>{{ $product->size ?? '-' }}</td>
                                    <td>{{ $product->color ?? '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                    <td class="text-center fw-semibold">{{ $product->stock }}</td>
                                    <td class="text-center text-nowrap">
                                        {{-- Preview read-only produk, ditampilkan lewat overlay di dalam
                                             halaman ini supaya Manager tidak berpindah ke halaman
                                             publik /toko/produk/{id}. --}}
                                        <button type="button" class="btn btn-outline-info btn-sm btn-lihat-produk-admin"
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
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        {{-- Data form edit dibaca JS dari atribut data-* agar tidak perlu request ulang --}}
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-edit-produk-admin"
                                            data-edit-produk="{{ $product->id }}"
                                            data-nama="{{ $product->name }}"
                                            data-kategori="{{ $product->category->name ?? '' }}"
                                            data-type="{{ $product->type ?? '' }}"
                                            data-size="{{ $product->size ?? '' }}"
                                            data-color="{{ $product->color ?? '' }}"
                                            data-harga="{{ (int) $product->price }}"
                                            data-deskripsi="{{ $product->description ?? '' }}"
                                            data-gambar="{{ $product->image_url ?? '' }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Hapus produk &quot;{{ $product->name }}&quot;? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus produk">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">Belum ada produk. Master produk ditambahkan dari dashboard Staff.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= PERSETUJUAN RESTOCK (OAS) ================= --}}
    <div id="persetujuan-restock">
        <h4 class="mb-3"><i class="bi bi-check-circle me-2"></i>Persetujuan Restock Barang (OAS)</h4>

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
    </div>

    {{-- ================= OVERLAY EDIT PRODUK =================
         Sama seperti di halaman staff, overlay memakai CSS kustom (position:fixed)
         supaya tidak bergantung pada perilaku modal Bootstrap. --}}
    <div class="produk-overlay" id="editProdukAdminOverlay" role="dialog" aria-modal="true" aria-labelledby="editProdukAdminTitle" hidden>
        <div class="produk-overlay-box">
            <div class="produk-overlay-head">
                <h5 class="mb-0" id="editProdukAdminTitle">
                    <i class="bi bi-pencil-square me-2"></i>Edit Produk
                </h5>
                <button type="button" class="btn-close" id="editProdukAdminClose" aria-label="Tutup"></button>
            </div>

            <form action="" method="POST" id="editProdukAdminForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <input type="hidden" name="_produk_id" id="editProdukAdminId" value="">

                <div class="produk-overlay-body">
                    <div id="editProdukAdminError" class="alert alert-danger d-none"></div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="editAdminNama" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editAdminNama" class="form-control" maxlength="100" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminKategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="category" id="editAdminKategori" class="form-control" maxlength="50"
                                list="editAdminKategoriList" required>
                            <datalist id="editAdminKategoriList">
                                @foreach($categories as $category)
                                    <option value="{{ $category->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminType" class="form-label">Type</label>
                            <input type="text" name="type" id="editAdminType" class="form-control" maxlength="50">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminSize" class="form-label">Size</label>
                            <input type="text" name="size" id="editAdminSize" class="form-control" maxlength="10">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminColor" class="form-label">Color</label>
                            <input type="text" name="color" id="editAdminColor" class="form-control" maxlength="30">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="editAdminHarga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="editAdminHarga" class="form-control" min="0" step="1" required>
                        </div>

                        <div class="col-12">
                            <label for="editAdminDeskripsi" class="form-label">Deskripsi Produk</label>
                            <textarea name="description" id="editAdminDeskripsi" rows="4" class="form-control" maxlength="2000"></textarea>
                            <div class="form-text">Maksimal 2000 karakter. Tampil di halaman detail produk pelanggan.</div>
                        </div>

                        <div class="col-12">
                            <label for="editAdminImage" class="form-label">Ganti Foto Produk</label>
                            <input type="file" name="image" id="editAdminImage" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Kosongkan bila tidak ingin mengganti foto.</div>

                            <div class="d-flex align-items-center gap-2 mt-2">
                                <img id="editAdminImagePreview" src="" alt="Foto produk saat ini" class="produk-preview d-none">
                                <span id="editAdminImageInfo" class="text-muted small">Produk ini belum memiliki foto.</span>
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="editAdminRemoveImage" name="remove_image">
                                <label class="form-check-label" for="editAdminRemoveImage">Hapus foto produk ini</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Stok tidak dapat diubah dari form ini. Perubahan stok hanya dilakukan lewat persetujuan restock.
                    </div>
                </div>

                <div class="produk-overlay-foot">
                    <button type="button" class="btn btn-outline-secondary" id="editProdukAdminBatal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= OVERLAY LIHAT PRODUK (READ-ONLY) =================
         Tanpa <form> sama sekali, hanya teks baca-saja dan satu tombol Tutup,
         sehingga Manager tidak bisa mengedit atau menghapus dari sini. --}}
    <div class="produk-overlay" id="lihatProdukAdminOverlay" role="dialog" aria-modal="true" aria-labelledby="lihatProdukAdminTitle" hidden>
        <div class="produk-overlay-box">
            <div class="produk-overlay-head">
                <h5 class="mb-0" id="lihatProdukAdminTitle">
                    <i class="bi bi-eye me-2"></i>Lihat Produk
                </h5>
            </div>

            <div class="produk-overlay-body">
                <div class="produk-detail-media">
                    <img id="lihatProdukAdminFoto" class="produk-detail-photo d-none" alt="Foto produk">
                    <div id="lihatProdukAdminTanpaFoto" class="produk-detail-fallback">
                        <i class="bi bi-image me-2"></i>Produk ini belum punya foto
                    </div>
                </div>

                <dl class="row mb-0">
                    <dt class="col-sm-4">Nama Produk</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminNama">-</dd>

                    <dt class="col-sm-4">Kategori</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminKategori">-</dd>

                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminType">-</dd>

                    <dt class="col-sm-4">Size</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminSize">-</dd>

                    <dt class="col-sm-4">Color</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminColor">-</dd>

                    <dt class="col-sm-4">Harga</dt>
                    <dd class="col-sm-8 fw-semibold" id="lihatProdukAdminHarga">-</dd>

                    <dt class="col-sm-4">Stok</dt>
                    <dd class="col-sm-8" id="lihatProdukAdminStok">-</dd>
                </dl>

                <hr class="my-3">

                <h6 class="mb-2">Deskripsi Produk</h6>
                <div id="lihatProdukAdminDeskripsi" class="produk-detail-deskripsi">Belum ada deskripsi untuk produk ini.</div>

                <div class="alert alert-secondary mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Halaman ini hanya menampilkan data. Untuk mengubah produk gunakan tombol pensil di
                    baris tabel, dan penambahan stok dilakukan lewat persetujuan restock.
                </div>
            </div>

            <div class="produk-overlay-foot">
                <button type="button" class="btn btn-outline-secondary" id="lihatProdukAdminTutup">Tutup</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const overlay = document.getElementById('editProdukAdminOverlay');
            const form = document.getElementById('editProdukAdminForm');
            if (!overlay || !form) return;

            const fields = {
                nama: document.getElementById('editAdminNama'),
                kategori: document.getElementById('editAdminKategori'),
                type: document.getElementById('editAdminType'),
                size: document.getElementById('editAdminSize'),
                color: document.getElementById('editAdminColor'),
                harga: document.getElementById('editAdminHarga'),
                deskripsi: document.getElementById('editAdminDeskripsi'),
                image: document.getElementById('editAdminImage'),
                removeImage: document.getElementById('editAdminRemoveImage'),
                preview: document.getElementById('editAdminImagePreview'),
                info: document.getElementById('editAdminImageInfo'),
                error: document.getElementById('editProdukAdminError'),
                produkId: document.getElementById('editProdukAdminId'),
            };
            const EDIT_URL = @json(route('admin.products.update', ['product' => 0]));

            const setPreview = (src) => {
                if (src) {
                    fields.preview.src = src;
                    fields.preview.classList.remove('d-none');
                    fields.info.classList.add('d-none');
                } else {
                    fields.preview.removeAttribute('src');
                    fields.preview.classList.add('d-none');
                    fields.info.classList.remove('d-none');
                }
            };

            const openOverlay = (btn) => {
                form.action = EDIT_URL + btn.dataset.editProduk;
                fields.produkId.value = btn.dataset.editProduk || '';
                fields.nama.value = btn.dataset.nama || '';
                fields.kategori.value = btn.dataset.kategori || '';
                fields.type.value = btn.dataset.type || '';
                fields.size.value = btn.dataset.size || '';
                fields.color.value = btn.dataset.color || '';
                fields.harga.value = btn.dataset.harga || '';
                fields.deskripsi.value = btn.dataset.deskripsi || '';
                fields.image.value = '';
                fields.removeImage.checked = false;
                fields.image.disabled = false;
                fields.error.classList.add('d-none');
                setPreview(btn.dataset.gambar || '');

                overlay.hidden = false;
                document.body.style.overflow = 'hidden';
                fields.nama.focus();
            };

            const closeOverlay = () => {
                overlay.hidden = true;
                document.body.style.overflow = '';
            };

            document.querySelectorAll('.btn-edit-produk-admin').forEach(btn => {
                btn.addEventListener('click', () => openOverlay(btn));
            });

            document.getElementById('editProdukAdminClose').addEventListener('click', closeOverlay);
            document.getElementById('editProdukAdminBatal').addEventListener('click', closeOverlay);
            overlay.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(); });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !overlay.hidden) closeOverlay();
            });

            // Mencentang "hapus foto" menonaktifkan input file supaya tidak
            // ada dua aksi yang bertabrakan dalam satu submit.
            fields.removeImage.addEventListener('change', () => {
                setPreview('');
                fields.image.disabled = fields.removeImage.checked;
            });

            // Kalau validasi update produk gagal, form dikembalikan ke halaman ini.
            // Overlay langsung dibuka lagi dengan pesan error supaya Manager
            // tidak perlu mencari produknya satu per satu.
            @if($errors->any() && old('_produk_id'))
                const btn = document.querySelector('.btn-edit-produk-admin[data-edit-produk="{{ old('_produk_id') }}"]');
                if (btn) {
                    openOverlay(btn);
                    fields.error.textContent = @json($errors->first());
                    fields.error.classList.remove('d-none');
                }
            @endif
        })();

        /* ================= OVERLAY LIHAT PRODUK (READ-ONLY) ================= */
        (function () {
            const lihatOverlay = document.getElementById('lihatProdukAdminOverlay');
            const editOverlay = document.getElementById('editProdukAdminOverlay');
            if (!lihatOverlay) return;

            const lihatFields = {
                nama: document.getElementById('lihatProdukAdminNama'),
                kategori: document.getElementById('lihatProdukAdminKategori'),
                type: document.getElementById('lihatProdukAdminType'),
                size: document.getElementById('lihatProdukAdminSize'),
                color: document.getElementById('lihatProdukAdminColor'),
                harga: document.getElementById('lihatProdukAdminHarga'),
                stok: document.getElementById('lihatProdukAdminStok'),
                deskripsi: document.getElementById('lihatProdukAdminDeskripsi'),
                foto: document.getElementById('lihatProdukAdminFoto'),
                tanpaFoto: document.getElementById('lihatProdukAdminTanpaFoto'),
            };
            const KOSONG = '-';
            const DESKRIPSI_KOSONG = 'Belum ada deskripsi untuk produk ini.';
            let triggerTerakhir = null;

            // textContent dipakai agar nama/deskripsi berisi tanda kutip atau
            // tag tidak di-parse sebagai HTML.
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

                if (editOverlay && !editOverlay.hidden) {
                    editOverlay.hidden = true;
                }

                triggerTerakhir = btn;
                lihatOverlay.hidden = false;
                document.body.style.overflow = 'hidden';
                document.getElementById('lihatProdukAdminTutup').focus();
            };

            const closeLihatOverlay = () => {
                lihatOverlay.hidden = true;
                document.body.style.overflow = '';
                if (triggerTerakhir) triggerTerakhir.focus();
                triggerTerakhir = null;
            };

            document.querySelectorAll('.btn-lihat-produk-admin').forEach(btn => {
                btn.addEventListener('click', () => openLihatOverlay(btn));
            });

            document.getElementById('lihatProdukAdminTutup').addEventListener('click', closeLihatOverlay);
            lihatOverlay.addEventListener('click', (e) => { if (e.target === lihatOverlay) closeLihatOverlay(); });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !lihatOverlay.hidden) closeLihatOverlay();
            });
        })();
    </script>
@endpush
