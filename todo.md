# To-Do List Pengembangan TPS & OAS

- [x] Sistem Login Multi-Role (Pelanggan, Kasir, Admin)
- [x] OAS Restock Request dari Staff/Kasir
- [x] OAS Restock Approval dari Manager/Admin
- [x] Integrasi Katalog Produk di `/toko`
- [x] Fitur Transaksi / Beli Produk pada Katalog Pelanggan
- [x] Polishing Front-End & UI/UX (Bootstrap / CSS Custom)
- [x] Modal Konfirmasi Pembelian & Payment Method
- [x] Monitoring Stok Realtime (Staff & Admin)
- [x] Sidebar Layout Bootstrap 5 (Staff & Admin)
- [x] Refactoring Sidebar UI (SantriKoding Style) - Staff & Admin Dashboard
- [x] Modul KMS Chatbot FAQ Automation (Migration, Model, Seeder, Controller, API, Widget)
- [x] Refactor Dashboard Staff jadi 5 Tab (Dashboard, Monitoring Stok, Pengajuan Restock, Riwayat Restock, Pengaturan)
- [x] Tabel Detail Produk Lengkap + Search/Filter Client-Side (Monitoring Stok)
- [x] Pemisahan Tabel Pending-Only (Pengajuan) vs Riwayat Semua Status (Riwayat Restock)
- [x] Navigasi Tab Seamless tanpa reload + Sinkronisasi Hash URL (Bootstrap Tab)
- [x] Card "Pengaturan & Keamanan Akun" (Logout Akun) di menu Pengaturan, hapus Logout dari bawah sidebar
- [x] Penyesuaian section dashboard: ID polos (`#dashboard` dll), JS navigasi manual show/hide, input search `#search-product`
- [x] Fitur Tambah Produk Baru (modal Bootstrap + validasi + route `staff.products.store`)
- [x] Input Category fleksibel (text + datalist, `Category::firstOrCreate` untuk kategori baru)
- [x] Shortcut "+ Produk Baru" di tab Pengajuan Restock + redirect kembali ke tab asal
- [x] Tambah Produk dipindah jadi form inline card "Tambah Master Produk Baru" di bawah Pengajuan Restock (modal dihapus)
- [x] Dropdown Kategori & Type bertingkat (select + opsi "+ Tambah ... Baru..." + input teks kondisional, tanpa jQuery/Select2)
- [x] Produk baru otomatis terpilih di dropdown "Pilih Produk" via `?produk=<id>`
- [x] Field "Stok Awal" dihapus â€” produk baru selalu didaftarkan dengan stok 0, diisi lewat pengajuan restock
- [x] Redirect `storeRestock` diarahkan eksplisit ke `#pengajuan-restock` (bukan `back()` yang menghilangkan hash)
- [x] Tab "Riwayat Restock" â†’ "Riwayat Stok" (`#riwayat-stok`) di sidebar staff & admin
- [x] Audit trail "Riwayat Aktivitas Stok": gabungkan Produk Baru + Pengajuan Restock, urut dari terbaru
- [x] Fix fatal `format() on null` â€” null-safe tanggal di staff dashboard, admin dashboard, dan `$riwayatStok`
- [x] Migration kolom `products.image` (nullable, setelah `color`) + `php artisan migrate --force`
- [x] Upload foto produk: validasi `mimes:jpg,jpeg,png,webp|max:2048`, simpan ke `public/images/produk` (tanpa `storage:link`)
- [x] Form staff multipart + field "Foto Produk" + blok `@error('image')`
- [x] Accessor `Product::image_url` + `'image'` di `$fillable`
- [x] Rewrite katalog `toko/index.blade.php` jadi grid Shoplytic modern (sidebar, topbar, panel filter, grid responsif)
- [x] Grid server-rendered `@forelse($products)` dengan `data-nama`/`data-harga`/`data-kategori` (tanpa mock JS array)
- [x] Filter client-side: checkbox kategori (dari DB), rentang harga, dual-handle slider 0-1.000.000, tombol Terapkan + Reset
- [x] Dual search tersinkron (`#globalSearch` â†” `#search`) + counter `#count` + pesan `#noMatch`
- [x] Dark mode toggle via CSS variable tanpa reload/localStorage
- [x] Tampilan foto produk di card + fallback emoji per kategori saat foto kosong
- [x] Modal pembelian di card baru dengan `BUY_URL` dari `route()` dan batas kuantitas = stok
- [x] Chatbot dipertahankan di katalog baru + `<meta name="csrf-token">` (fix POST 419)
- [x] `TokoController::index()` eager load `with('category')` + kirim `$categories` untuk filter
- [x] Fix bug: `'image' => $data['image'] ?? null` ditambahkan ke `Product::create()` (file sempat terupload tapi kolom `image` tetap `NULL`)
- [x] Verifikasi manual via HTTP kernel (render 200, filter id, upload end-to-end, cleanup data uji) â€” tanpa `artisan test`
- [x] Phase 15 â€” Palet katalog ganti Oranye â†’ Biru Modern (`--accent:#2563eb`, `--accent-soft:#e8f0fe`, track slider `#c7dcfd`, dark mode `#1e3a5f`)
- [x] Phase 15 â€” Sidebar direstrukturisasi ala Shopee: 5 menu datar (Overview, Katalog Produk, Keranjang Belanja, Pesanan Saya, Produk Favorit), sub-menu + link "Log Out" dihapus
- [x] Phase 15 â€” Helpinan "Bantuan" tetap di `.nav.bottom`
- [x] Phase 15 â€” Search bar topbar (`#globalSearch` + CSS `.gsearch`) dihapus, hanya `#search` di header katalog
- [x] Phase 15 â€” Logout dipindah dari sidebar ke dalam modal profil
- [x] Phase 15 â€” Komponen profil topbar jadi `<button id="profileBtn">` (clickable + keyboard, `aria-haspopup`/`aria-expanded`) membuka modal profil berisi nama, email, role, dan tombol "Keluar dari Akun" (`POST route('logout')` + `@csrf`)
- [x] Phase 15 â€” Escape menutup modal pembelian dan modal profil sekaligus
- [x] Phase 15 â€” Copy topbar diubah ke "Katalog Produk" / "Temukan pakaian sesuai kebutuhan dan kebutuhanmu."
- [x] Phase 15 â€” Verifikasi via HTTP kernel: palet, sidebar, search, logout, modal profil, dan regresi chatbot/buy/filter (tanpa `artisan test`)
- [x] Phase 16 â€” Font katalog: **Plus Jakarta Sans** (fallback Inter) + base font 13px â†’ **15px**, line-height 1.5, dan ~23 ukuran turunan diperbesar (judul, card, badge, breadcrumb, modal, filter)
- [x] Phase 16 â€” Sizing komponen ikut menyesuaikan (checkbox 15â†’18px, thumb slider 12â†’15px, avatar/ic 32/34â†’36px, minmax card 190â†’215px, kolom filter 174â†’196px)
- [x] Phase 16 â€” Dark mode high contrast: `--bg:#121827`, `--card:#1f2937`, `--ink:#f3f4f6`, `--line:#374151`, aksen dinaikkan ke `#3b82f6`
- [x] Phase 16 â€” Token kontras baru: `--on-accent`, `--alert`, `--danger-soft`, `--danger` (tiap warna punya versi gelap)
- [x] Phase 16 â€” Fix deteksi mode gelap (sebelumnya membandingkan `--bg` dengan string yang tidak ada di palet mana pun, jadi selalu salah)
- [x] Phase 16 â€” Warna kaku gelap yang tidak terbaca di mode gelap diperbaiki: `.nav a` `#444` â†’ `var(--ink)`, `.checks label` `#555` â†’ `var(--ink)`, `.ic` `#fff` â†’ `var(--card)`
- [x] Phase 16 â€” Emoji sidebar diganti **6 ikon SVG inline** (Lucide style, `stroke=currentColor`): LayoutDashboard, ShoppingBag, ShoppingCart, Package, Heart, HelpCircle
- [x] Phase 16 â€” Sidebar nav diberi `aria-label`, tiap SVG `aria-hidden="true"`, teks menu dibungkus `<span>`
- [x] Phase 16 â€” Route `toko.pesanan` + `TokoController::pesanan()` (filter `customer_id = auth()->id()`)
- [x] Phase 16 â€” Halaman baru `resources/views/toko/pesanan.blade.php`: 4 kartu ringkasan, daftar pesanan bernomor, badge status, empty state
- [x] Phase 16 â€” Link sidebar: Overview & Katalog â†’ `toko.index`; Pesanan Saya â†’ `toko.pesanan`; Keranjang/Favorit/Bantuan â†’ modal info (tidak ada tabel cart/favorit di skema)
- [x] Phase 16 â€” Modal `#infoModal` + object `infoContent` (judul, ikon SVG, teks jujur "fitur dalam pengembangan")
- [x] Phase 16 â€” Escape menutup buy modal, info modal, dan modal profil sekaligus
- [x] Phase 16 â€” Perbaikan teks changelog yang korup (karakter acak & emoji hilang) di Phase 8, 10, 14, 15, 16
- [x] Phase 16 â€” Verifikasi via HTTP kernel: `GET /toko` 200, `GET /toko/pesanan` 200, cabang `@empty` pesanan, regresi chatbot/buy/filter/profil/logout (tanpa `artisan test`)

- [x] Phase 17 - Migration `2026_10_01_000001_add_description_to_products_table.php` (TEXT nullable, `after('image')`) - `products.image` sudah ada dari Phase 14, jadi tidak dibuat ulang
- [x] Phase 17 - Migration `2026_10_01_000002_create_favorites_table.php` (user_id & product_id cascade, unique compound `user_id+product_id`, timestamps)
- [x] Phase 17 - Jalankan `php artisan migrate --force` (bukan `migrate:fresh`) - 2 migration DONE
- [x] Phase 17 - Model `Favorite` + relasi `Product::favorites()` dan `User::favorites()`
- [x] Phase 17 - Trait `app/Support/HandlesProductImage.php` (`storeProductImage`, `syncProductImage`, `deleteProductImage`) - hapus file lama hanya setelah file baru tersimpan
- [x] Phase 17 - Folder gambar tetap `public/images/produk/` (tanpa `storage:link`)
- [x] Phase 17 - `StaffController::storeProduct()` menerima `description`; logika upload dipindah ke trait
- [x] Phase 17 - `StaffController::updateProduct()` (baru) - `abort_unless` role staff/admin, boleh ganti kategori, `stock` tetap terkunci
- [x] Phase 17 - `ManagerController::storeProduct()` + `updateProduct()` + `destroyProduct()` (baru) - `storeProduct`/`destroyProduct` khusus role admin
- [x] Phase 17 - Delete produk **ditolak** bila ada `transaction_details` (FK tanpa cascade) agar riwayat penjualan tetap utuh
- [x] Phase 17 - Route: `toko.show`, `toko.favorit`, `toko.favorit.toggle`, `staff.products.update`, `admin.products.store`, `admin.products.update`, `admin.products.destroy`
- [x] Phase 17 - `TokoController::show()` / `toggleFavorite()` / `favorit()` (baru) - toggle pakai `first`+`create`/`delete` (bukan `updateOrCreate`) demi unique index, balas JSON untuk `fetch()`
- [x] Phase 17 - Layout bersama `resources/views/layouts/toko-shell.blade.php` (sidebar, topbar, dark mode, modal profil, modal info, chatbot) - katalog tidak diubah strukturnya
- [x] Phase 17 - Halaman `toko/show.blade.php` (foto besar, deskripsi, spesifikasi, favorit, tombol beli) dan `toko/favorit.blade.php` (grid + empty state)
- [x] Phase 17 - Katalog: tombol hati SVG per card, judul card link ke detail, sidebar Favorit -> `route('toko.favorit')`, entri `infoContent.favorit` dihapus
- [x] Phase 17 - Form edit pakai **overlay kustom** (bukan Modal Bootstrap) karena `StaffDashboardTabTest` melarang `bootstrap.Modal.getOrCreateInstance`; overlay di luar `.dashboard-section` agar tidak ikut `d-none`
- [x] Phase 17 - Overlay buka kembali dengan nilai `old()` bila validasi ditolak; checkbox "Hapus foto" menonaktifkan input file
- [x] Phase 17 - Admin: section `#kelola-produk` (form tambah + tabel produk) dengan tombol Edit dan Hapus (`confirm()`), menu sidebar "Kelola Produk"
- [x] Phase 17 - Verifikasi tanpa `artisan test`: `php -l` 10 file OK, `route:list` 7 route baru terdaftar, `db:table` schema sesuai
- [x] Phase 17 - **BUG 1** `/toko/favorit` HTTP 500 - `whereHas` + `latest('favorites.created_at')` gagal (`Unknown column`) karena tabel tidak di-join; diperbaiki dengan `join('favorites')` + `orderByDesc` + `select('products.*')`
- [x] Phase 17 - **BUG 2** `POST /toko/favorit/{product}` HTTP 500 - `toggleFavorite()` memakai `$request` tanpa diinjeksi (`Undefined variable $request`); diperbaiki jadi `toggleFavorite(Request $request, Product $product)`
- [x] Phase 17 - **BUG 3** `App\Models\TransactionDetail` tidak pernah ada padahal dipakai `ManagerController`; model dibuat + relasi `Transaction::details()`
- [x] Phase 17 - **BUG 4** `StaffController::storeProduct()` tidak punya cek role (pelanggan bisa tambah produk); ditambah `abort_unless(role in ['staff','admin'])`
- [x] Phase 17 - Overlay admin kini punya hidden `_produk_id` dan reopen + pesan error saat validasi ditolak
- [x] Phase 17 - Verifikasi render nyata (bukan `artisan test`): `/toko`, `/toko/produk/{id}`, `/toko/favorit`, `/staff/dashboard`, `/admin/dashboard` semua HTTP 200
- [x] Phase 17 - Assertion `StaffDashboardTabTest` diceck satu per satu terhadap HTML render: 11 negatif + 18 positif lolos, `dashboard-section`=5, `d-none`=4, `logout`=1, `produk-overlay`=1
- [x] Phase 17 - Otorisasi diuji dengan role nyata: pelanggan 403 di semua route produk, staff 403 untuk `admin.products.destroy`, tamu 302 ke login
- [x] Phase 17 - Gate riwayat penjualan diuji dengan transaksi nyata: delete ditolak, produk + `transaction_details` + `transactions` tetap utuh
- [x] Phase 17 - Alur foto diuji penuh: upload, update tanpa file (foto lama bertahan), ganti foto (file lama terhapus), hapus foto (kolom null + file terhapus), dan `stock` tetap terkunci
- [x] Phase 17 - Semua data uji dibersihkan: DB kembali ke 4 produk / 4 kategori / 0 favorit / 0 `transaction_details`, folder `images/produk/` kosong

### Revisi Phase 17 (RUD + Preview Detail)
- [x] Revisi - Form "Tambah Master Produk" **dihapus** dari `admin/dashboard.blade.php`; kartu "Daftar Master Produk" jadi satu-satunya kartu di `#kelola-produk`; teks baris kosong diarahkan ke dashboard Staff
- [x] Revisi - Route `admin.products.store` dihapus dari `routes/web.php` dan `ManagerController::storeProduct()` dihapus; Manager kini fokus RUD + Approval
- [x] Revisi - Tombol "Lihat" (preview detail) ditambahkan di sel `Aksi` tabel produk **Staff** (`btn-lihat-produk`) dan **Admin** (`btn-lihat-produk-admin`), menuju `route('toko.show', $product->id)` dengan `target="_blank" rel="noopener"` - **berlaku hanya untuk versi pertama, sudah diganti modal (lihat "Revisi 2" di bawah)**
- [x] Revisi - Jumlah kolom tidak berubah (`colspan="10"` tetap benar), `StaffDashboardTabTest` diuji ulang dan semua assertion tetap lolos
- [x] Revisi - Diverifikasi `/toko/produk/{id}` bisa dibuka role staff maupun admin (HTTP 200)
- [x] Revisi - Catatan ditambahkan ke `changelog.md`: section "Fitur Kelola Foto Produk" (tabel 4 perilaku termasuk hapus foto) + section "Revisi Phase 17" berisi kronologi insiden
- [ ] Revisi - **Foto produk #3 "Kemeja Hitam Polos" terhapus permanen** oleh agen saat verifikasi (salah mengira data uji). `products.image` sudah di-`NULL`, deskripsi aman, tapi file `public/images/produk/1790758786_ttYGgZ65.jpg` (1024x1024) tidak bisa dipulihkan - **perlu diunggah ulang oleh pengguna**

### Revisi 2 (Tombol "Lihat" jadi Modal Read-Only In-Dashboard)
- [x] Tombol "Lihat" di Staff & Admin diubah dari `<a href>` + `target="_blank"` menjadi `<button type="button">` yang membuka modal read-only, **tanpa navigasi** ke halaman publik
- [x] Overlay memakai CSS `.produk-overlay` yang sudah ada (bukan Bootstrap Modal), ditempatkan di luar wrapper/tab `d-none` seperti overlay Edit
- [x] Isi modal: Foto, Nama, Kategori, Type, Size, Color, Harga, Stok, Deskripsi - semuanya teks baca-saja di dalam `<dl>`, **tanpa `<form>`/`<input>`/`<textarea>`/`<select>`**, hanya satu tombol Tutup (tanpa Simpan/Hapus)
- [x] Foto memakai `image_url` dengan fallback "Produk ini belum punya foto"; deskripsi memakai `textContent` + `white-space: pre-wrap` supaya baris baru tidak collapsed
- [x] Interaksi: tutup lewat tombol Tutup, klik backdrop, atau `Escape`; fokus ke tombol Tutup saat dibuka dan kembali ke tombol pemicu saat ditutup; scroll body dikunci selama overlay terbuka
- [x] CSS baru di `layouts/app.blade.php`: `.produk-detail-media`, `.produk-detail-photo`, `.produk-detail-fallback`, `.produk-detail-deskripsi`, `.produk-detail-kosong`
- [x] `ManagerController::index()` - komentar basi dikoreksi (tidak lagi menyebut Manager boleh menambah produk)
- [x] Verifikasi: probe render read-only (144 pemeriksaan, 144 lolos) - 11 assertion negatif tetap absen termasuk `name="stock"`; hitungan presisi utuh; kedua halaman sudah tidak memuat `href` ke `/toko/produk/`; overlay Edit tidak mengalami regresi
- [x] Verifikasi: tidak ada perubahan data/berkas (4 produk, 4 kategori, 0 favorit, 0 `transaction_details`, 6 restock, 0 `image` non-null, 0 berkas di `public/images/produk/`)
- [x] `changelog.md` - section "Revisi 2" ditulis; sub-bagian 2 lama ditandai usang; karakter korup di `changelog.md:249` diperbaiki

### Phase 18 (Landing Page "La Mamora")
- [x] Phase 18 - Route `/` di `routes/web.php` menyediakan data DB ke view: `products` (+`category`), `categories`, `stats` (produk/pelanggan/kategori); import model ditambahkan; Pint bersih
- [x] Phase 18 - `resources/views/welcome.blade.php` ditulis ulang total dari template statis "Wearly" - hero, About + statistik angka, Koleksi, Kontak, Blog, Footer
- [x] Phase 18 - Brand **La Mamora** menggantikan "Wearly"/"TPS Pakaian Store" (judul, navbar, footer)
- [x] Phase 18 - Koleksi **DB-driven**: tab kategori dari `Category`, kartu produk dari `$products` dengan `image_url`/fallback emoji, harga Rupiah `number_format`, badge "Stok Habis", filter kategori client-side (`data-kategori`)
- [x] Phase 18 - Tombol `+` kartu produk: tamu -> buka modal login + toast; sudah login -> `route('toko.index')`
- [x] Phase 18 - Modal `<dialog>` auth terhubung backend: form `POST /login` + `/register` dengan `@csrf`, `password_confirmation`, render `@error` per field, auto-reopen via `old('_tab')` saat `AuthController` mengembalikan `back()->withErrors()`
- [x] Phase 18 - Navbar `@guest` (Masuk/Daftar) vs `@auth` (tombol dashboard per role: pelanggan `/toko`, staff `/staff/dashboard`, admin `/admin/dashboard`)
- [x] Phase 18 - Toast `session('success')` + `@error('credentials')`; section Kontak tampil toast demo (tanpa pengiriman email)
- [x] Phase 18 - `ExampleTest` diberi `RefreshDatabase` karena `/` kini query tabel `products` di sqlite in-memory test
- [x] Phase 18 - `StaffDashboardTabTest:179` assertion diperbaiki `selected="selected"` -> `selected` (directive `@selected` Laravel ini meng-`echo 'selected'` polos; failure pre-existing yang terkonfirmasi lewat baseline `git stash`)
- [x] Phase 18 - Verifikasi: `php artisan test` **7/7 lolos** (baseline: 6/7), Pint passed di semua berkas yang disentuh, probe HTTP `GET /` **28/29** (1 kegagalan = badge stok habis, wajar karena semua stok > 0), 0 direktif Blade sisa di output
- [x] Phase 18 - `changelog.md` ditulis (section Phase 18: perubahan, verifikasi, batasan)
- [x] Phase 18 Revisi 1 - Tab login modal landing kini **hanya Email + Password** - fix bug CSS `label[hidden]{display:none}` (rule `label{display:grid}` author-origin menimpa `[hidden]` UA, sehingga Nama & Konfirmasi Password tetap tampil); tab Register tidak berubah; tanpa menjalankan test apapun (permintaan pengguna)

### Phase 19 (MIS Analytics Dashboard Manager/Admin)
- [x] Phase 19 - `ManagerController::index()` - filter periode GET `dari`/`sampai` (default 6 bulan terakhir, rentang terbalik otomatis ditukar) -> `$periode`
- [x] Phase 19 - KPI Executive Summary (`$kpi`): Omset = `SUM(total)` transaksi periode, Produk Terjual = `SUM(quantity)` `transaction_details`, Stok Kritis = produk `stock < 5`, Restock Pending = `restock_requests` status pending
- [x] Phase 19 - Tren penjualan bulanan di-group di PHP (DB-agnostic), zero-fill per bulan -> `$trenLabels/$trenOmset/$trenTerjual`
- [x] Phase 19 - Top 5 terlaris dari agregat `transaction_details` (tanpa N+1, nama produk di-resolve lewat `whereIn` + `keyBy`)
- [x] Phase 19 - Exception reporting `$lowStock` (produk stok < 5) + Audit trail `$auditTrail` (restock + nama employee pengaju + status)
- [x] Phase 19 - Chart.js 4.4.1 CDN via `@push('scripts')`, diinit dengan guard `typeof Chart` + keberadaan canvas
- [x] Phase 19 - UI `#analisis-mis`: form filter tanggal GET (Terapkan/Reset), tombol Cetak `window.print()`, 4 kartu KPI, bar chart Omset + line chart Terjual (dual sumbu), doughnut Top 5 + tabel ringkas (empty state bila `transaction_details` kosong)
- [x] Phase 19 - `#stok-realtime` Low Stock Alert (tabel + badge Habis/Kritis; kosong -> alert "Semua stok aman") - sekaligus memperbaiki link sidebar mati
- [x] Phase 19 - `#riwayat-stok` Audit Trail (Tanggal, Produk, Pengaju+posisi, Jumlah, Status Approval, Catatan) - memenuhi assertion test `href="#riwayat-stok"` & `>Riwayat Stok</span>`
- [x] Phase 19 - CSS `@media print` via `@push('styles')`: sembunyikan sidebar/form/tombol/overlay, tampilkan `.print-header` Laporan MIS + periode
- [x] Phase 19 - Form "Tambah Master Produk Baru" dipastikan tetap absen di dashboard Admin (sudah tidak ada sejak Revisi Phase 17)
- [x] Phase 19 - Modal `#settingsModal` (sidebar) kini berisi tombol/logout form POST `route('logout')`, dibungkus `@if(role !== 'staff')` agar jumlah form logout di halaman staff tetap 1 (StaffDashboardTabTest:109)
- [x] Phase 19 - Verifikasi tanpa test: `php -l` controller OK, struktur section id + `@push/@endpush` dicek via grep, constraint test dibaca dari kode test
- [x] Phase 19 Revisi - `buyProduct()` kini menyimpan **`transaction_details`** (product_id, quantity, subtotal) dalam `DB::transaction()` atomik + hardening qty (`max(1, int)`) dan cek stok `< qty`
- [x] Phase 19 Revisi - Seeder baru `database/seeders/TransactionDetailSeeder.php` - isi detail trx ID 1-6, subtotal = harga produk x qty (cocok dengan `total`), idempoten (skip bila sudah ada)
- [x] Phase 19 Revisi - Seeder dijalankan: `php artisan db:seed --class=TransactionDetailSeeder` -> 6/6 "detail cocok dengan total"; run kedua semua dilewati; verifikasi baca: 7 baris, `SUM(subtotal)==total` untuk semua transaksi

### Tersisa
- [ ] Phase 18 - **Belum diverifikasi di browser** - perilaku JS landing (filter kategori koleksi, animasi reveal, buka/tutup `<dialog>` auth, auto-reopen saat validasi gagal, toast) masih perlu dicek manual
- [ ] Phase 18 - Form Kontak landing hanya toast demo, belum mengirim email
- [ ] Phase 18 - Halaman `/login` & `/register` (Blade terpisah) tidak disentuh - masih tampilan Bootstrap lama, berbeda dengan modal landing
- [ ] Phase 18 - `pint --test` seluruh repo masih gagal di 22 berkas pre-existing yang tidak disentuh (AuthController, model, migrasi, `routes/api.php`, dll) - tidak diperbaiki agar diff tidak melebar
- [ ] Phase 18 - Laravel Boost tidak dipasang (dilewati demi tidak mengubah `composer.json` untuk tugas UI) - `AGENTS.md` masih mewajibkannya untuk sesi berikutnya
- [ ] Phase 19 - **Belum diverifikasi di browser** - render Chart.js, filter tanggal GET, tombol Cetak (`window.print`), dan modal Pengaturan + logout masih perlu dicek manual; test suite **tidak dijalankan** sesuai permintaan (hanya `php -l`)
- [x] Phase 19 - ~~KPI "Produk Terjual" dan doughnut Top 5 = 0/kosong~~ **Selesai di Phase 19 Revisi**: `buyProduct()` menyimpan `transaction_details` + seeder mengisi data lama (Kemeja Hitam Polos kini top seller 5 pcs)
- [ ] Phase 19 - Chart.js dimuat dari CDN jsdelivr sehingga grafik butuh koneksi internet saat halaman dibuka
- [ ] Phase 17 - **Belum diverifikasi di browser** - perilaku JavaScript (tombol hati `fetch()`, buka/tutup overlay edit, `confirm()` hapus, chatbot, buka/tutup modal read-only + Escape + perpindahan fokus) masih perlu dicek manual
- [x] Phase 17 - ~~`buyProduct()` masih tidak mengisi `transaction_details`~~ **Selesai di Phase 19 Revisi**: checkout kini menyimpan detail + Seeder `TransactionDetailSeeder` mengisi trx ID 1-6 (7 baris)
- [ ] Phase 17 - Overlay staff & admin (edit + lihat) masih duplikasi markup - bisa dijadikan partial `@include` dengan prefix ID
- [ ] Dark mode - `.produk-overlay-box` masih `background: #fff` hardcoded, jadi overlay tetap terang saat mode gelap aktif (sudah bug sejak overlay Edit; belum diperbaiki)

### Phase 20 (Sidebar & Section-Switching Dashboard Manager)
- [x] Phase 20 - Link sidebar admin: Dashboard diberi `#dashboard`, link baru **Analisis Penjualan** -> `#analisis-mis`; link OAS dipertahankan bentuk `route('admin.dashboard')#xxx` (wajib untuk StaffDashboardTabTest:229,231)
- [x] Phase 20 - View admin dipecah jadi 6 section `.dashboard-section`: `#dashboard` (4 KPI + 3 widget sekilas: Stok Kritis / Restock Pending / Top Produk, masing-masing dengan link "Lihat selengkapnya"), `#analisis-mis` (filter + grafik + tombol Cetak), `#stok-realtime` (di-flatten dari bersarang), `#kelola-produk`, `#persetujuan-restock`, `#riwayat-stok`
- [x] Phase 20 - Tombol Cetak dipindah dari header halaman ke card MIS; overlay edit/lihat dan `.print-header` tetap di luar section
- [x] Phase 20 - JS pemilih section (pola staff: show/hide + sync hash + tutup sidebar mobile), navLinks difilter agar link modal Profil/Pengaturan tidak kena `preventDefault`; default load = hash > `#analisis-mis` (bila ada query filter) > `#dashboard`; canvas chart di-`resize()` saat section tampil
- [x] Phase 20 - Verifikasi tanpa test: `php artisan view:cache` sukses (lalu `view:clear`), `<div>` admin view seimbang 127/127, 6 section via grep, constraint test terpenuhi via grep sidebar, staff view tidak tersentuh
- [ ] Phase 20 - **Belum diverifikasi di browser** - klik menu sidebar, render chart setelah pindah section, filter tanggal, cetak, dan modal Profil/Pengaturan masih perlu dicek manual; test suite **tidak dijalankan** sesuai permintaan

### Phase 21 (Rebrand TPS Pakaian -> La Mamora)
- [x] Phase 21 - Logo sidebar admin/staff: `<i class="lm-mark">L</i>La Mamora.` + CSS `.sidebar-brand .lm-mark` (kotak navy #14213d 30px, radius 9px, huruf putih - persis `.logo i` landing)
- [x] Phase 21 - Logo dashboard pelanggan (`toko-shell:157`) & katalog (`index:199`): glyph `? TPS Pakaian` -> `L La Mamora.`; CSS `.logo` diganti bg navy #14213d (sebelumnya var(--accent) biru)
- [x] Phase 21 - 14 teks branding diganti: judul tab (app default, toko-shell, index, pesanan, login, register), heading default toko-shell, heading login, aria-label + judul + sapaan chatbot (toko-shell & index)
- [x] Phase 21 - Verifikasi tanpa test: grep case-sensitive `TPS` di resources/views = 0, grep `La Mamora` = 24 spot, `php artisan view:cache` sukses lalu `view:clear`
- [ ] Phase 21 - **Belum diverifikasi di browser** - tampilan kotak "L" navy di sidebar/katalog/dashboard pelanggan masih perlu dicek manual; test suite **tidak dijalankan** sesuai permintaan
- [ ] Phase 21 - `ChatbotSeeder` (DB) masih menyebut "TPS Pakaian" di 2 jawaban bot - tidak diubah sesuai cakupan yang dipilih; bila perlu konsisten, update isi `replies` + seed ulang
- [ ] Phase 21 - `public/favicon.ico` tidak diganti (tidak ada asset logo La Mamora; landing juga tanpa favicon)

### Phase 22 (Design System Dashboard La Mamora)
- [x] Phase 22 - Keputusan pengguna: fase **22-28**, **middleware role baru** dibuat sendiri, **Laravel Boost dilewati**, **file CSS statis** (bukan Vite/Tailwind)
- [x] Phase 22 - `public/css/la-mamora.css` (baru): token canonical `--lm-*` (navy/sky/sky-2/orange/muted/line/white **persis** sama dengan landing Phase 18) + status + tipografi + radius + shadow + spasi + z-index
- [x] Phase 22 - Alias `:root` (`--navy`, `--sky`, `--sky2`, `--orange`, `--muted`, `--line`, `--white`) -> `var(--lm-*)` supaya landing page tidak perlu ditulis ulang
- [x] Phase 22 - Override variabel Bootstrap 5.3 (`--bs-*`) diarahkan ke palet La Mamora; nilai `-rgb` ikut diatur agar utility `bg-opacity-10` tetap jalan
- [x] Phase 22 - Komponen `.lm-*`: topbar (`.lm-topbar/.lm-topbar-title/.lm-chip/.lm-topbar-toggle`), statistik (`.lm-stat-card/.lm-stat-icon/.lm-stat-label/.lm-stat-value/.lm-stat-note`), status (`.lm-badge*`), `.lm-empty`, `.lm-table-wrap`, `.lm-form-grid`, `.lm-matrix` (AHP), `.lm-score/.lm-score-bar`, `.lm-criterion`, `.lm-metric*`, `.min-w-0`
- [x] Phase 22 - Override Bootstrap **di-scope `body.lm-dashboard`** -> landing page & dashboard pelanggan tidak berubah visual
- [x] Phase 22 - Layout dashboard: `.sidebar` (border navy + shadow), `.sidebar-brand .lm-mark` (kotak "L" navy 30px), `.sidebar-nav .nav-link` (+active/hover), sidebar off-canvas + `.sidebar-overlay` untuk mobile
- [x] Phase 22 - `resources/views/layouts/app.blade.php`: `<body class="lm-dashboard">`, link CSS bersama, topbar baru (tombol sidebar + judul + chip tanggal/peran), CSS overlay produk di-tokenisasi
- [x] Phase 22 - JS sinkron judul topbar: atribut `data-lm-title` per section + `MutationObserver` (deteksi pertukaran class `d-none`)
- [x] Phase 22 - `resources/views/admin/dashboard.blade.php`: 6 section dapat `data-lm-title`; 4 kartu KPI pakai `.lm-stat-*`
- [x] Phase 22 - `resources/views/staff/dashboard.blade.php`: 5 section dapat `data-lm-title`; 3 kartu statistik pakai `.lm-stat-*` (class `dashboard-section` **tidak** diubah agar assertion test tetap lolos)
- [x] Phase 22 - `resources/views/welcome.blade.php`: blok `:root` inline dihapus, link CSS bersama ditambahkan (landing tidak didesain ulang)
- [x] Phase 22 - Verifikasi: `php artisan test` **7/7 lolos, 98 assertions** (identik baseline); `php artisan view:cache` sukses; probe HTTP `GET /` = 200 (24.375 byte) dan `GET /css/la-mamora.css` = 200 (22.696 byte); `pint --test` gagal hanya di **22 berkas pre-existing** yang tidak disentuh
- [x] Phase 22 - `changelog.md` ditulis (Phase 22: perubahan berkas, hasil verifikasi, batasan yang disepakati)
- [ ] Phase 22 - **Belum diverifikasi di browser** - tampilan akhir dashboard admin/staff, sidebar mobile, dan perpindahan judul topbar saat pindah section masih perlu dicek manual

### Phase 23 (Database DSS)
- [x] Phase 23 - 8 migration baru (prefix `2026_10_21_`): `suppliers`, `product_suppliers`, `dss_criteria`, `dss_criteria_comparisons`, `dss_runs`, `dss_supplier_scores`, `dss_restock_recommendations`, + `restock_requests` (kolom `supplier_id`/`dss_run_id`)
- [x] Phase 23 - FK `restock_requests` hanya dipasang di MySQL (SQLite tidak mendukung penambahan FK ke tabel existing) - sudah diuji di kedua driver
- [x] Phase 23 - 7 model baru (`Supplier`, `ProductSupplier`, `DssCriterion`, `DssCriterionComparison`, `DssRun`, `DssSupplierScore`, `DssRestockRecommendation`) + relasi baru pada `Product`, `RestockRequest`, `User`
- [x] Phase 23 - Factory: `SupplierFactory`, `DssCriterionFactory` (state benefit/cost), `ProductSupplierFactory`, `ProductFactory`, `CategoryFactory`
- [x] Phase 23 - `DssSeeder`: 5 pemasok, 15 penawaran produk, 6 kriteria AHP, 30 matriks perbandingan (konsisten CR = 0), idempoten, terdaftar di `DatabaseSeeder`
- [x] Phase 23 - `tests/Feature/DssSchemaTest.php`: 4 test (penawaran produk, kriteria + matriks, run + skor + rekomendasi, pengajuan restock tertaut DSS)
- [x] Phase 23 - Verifikasi: `migrate:fresh --seed` SQLite in-memory sukses (27 migration); `migrate --force` MySQL sukses tanpa merusak data lama; `php artisan test` **11/11 lolos, 119 assertions**; Pint passed untuk 20 berkas baru; matriks AHP dihitung ulang -> bobot 0.30/0.25/0.18/0.12/0.08/0.07, CR = 0

### Phase 24 (Service Layer DSS)
- [x] Phase 24 - `app/Services/AhpService.php` (baru): `calculate()` (normalisasi kolom -> bobot rata-rata baris -> lambda max dari matriks ASLI -> CI -> CR dengan RI Saaty 1-10), `buildMatrix()`, `analyze()` (baca DB, `criteria_weights` berkunci id kriteria), `CONSISTENCY_THRESHOLD = 0.10`
- [x] Phase 24 - `app/Services/VikorService.php` (baru): rumus baku `d_ij = w_j*(f*_j - f_ij)/(f*_j - f-_j)`, `S_i = SUM d_ij`, `R_i = MAX d_ij` (kecil = baik), `Q_i = v*(S_i - S*)/(S- - S*) + (1-v)*(R_i - R*)/(R- - R*)`; peringkat Q menaik (tie-break S lalu R), klasifikasi baku `Solusi Tunggal`/`Kompromi Alternatif 1 dan 2`/`Kompromi Semua Alternatif` (DQ = 1/(J-1)), flag `has_advantage`/`is_stable`; tanpa skor -> ranking kosong
- [x] Phase 24 - `app/Services/RestockRecommendationService.php` (baru): rata-rata penjualan 30 hari (hanya transaksi `completed`), demand lead time, safety stock (faktor 0,25), reorder point, pembulatan ke MOQ pemasok, `persist()` ke `dss_restock_recommendations`, `toRestockRequest()` (tertaut supplier + run), `pickSupplierOffer()` (VIKOR -> is_preferred -> pertama)
- [x] Phase 24 - `app/Services/ProductRecommendationService.php` (baru): `recommendFor()` + `recommendForGuest()`, skor explainable (+3 kategori favorit, +2 terlaris, +1 stok, -2 sudah dibeli) + array `reasons` untuk UI
- [x] Phase 24 - 6 test baru: `tests/Unit/AhpServiceTest.php` (7), `tests/Unit/VikorServiceTest.php` (12), `tests/Feature/AhpAnalysisTest.php` (4), `tests/Feature/DssPipelineTest.php` (2), `tests/Feature/RestockRecommendationServiceTest.php` (11), `tests/Feature/ProductRecommendationServiceTest.php` (8)
- [x] Phase 24 - Perhitungan manual terverifikasi: AHP `[[1,2,4],[1/2,1,2],[1/4,1/2,1]]` -> bobot 4/7, 2/7, 1/7, lambda max 3, CI 0, CR 0; VIKOR 3 alternatif -> S/R/Q & peringkat A > B > C (ambang 0,5)
- [x] Phase 24 - Probe MySQL: bobot AHP dari data tersimpan 0,30/0,25/0,18/0,12/0,08/0,07 (CR = 0), VIKOR mengurutkan 5 pemasok, analisis restock 5 produk, rekomendasi produk 4 item
- [x] Phase 24 - Verifikasi: `php artisan test` **51/51 lolos, 269 assertions** (baseline 11/11); `pint --test` passed untuk 9 berkas Phase 24, total repo tetap 22 berkas pre-existing gagal
- [x] Phase 24 - `changelog.md` ditulis (Phase 24: perubahan berkas, hasil verifikasi, batasan yang disepakati)
- [x] Phase 24 - Orkestrasi run analisis lengkap (**Buat `dss_runs` + `dss_supplier_scores`**) - selesai di Phase 25 via `DssAnalysisService::saveScores()` + `storeRun()`
- [x] Phase 24 - Data demo saat ini belum ada produk di bawah reorder point -> tabel restock akan kosong sampai riwayat transaksi 30 hari diisi (Phase 28) - **selesai di Phase 28** (`DssDemoTransactionSeeder`, 93 transaksi, 1 produk "Perlu Restock")

### Phase 23-28 (Modul DSS - Decision Support System)
- [x] Phase 23 - Database DSS: 7 migration baru (supplier, product_supplier, kriteria AHP, bobot AHP, perbandingan, hasil VIKOR, run analisis) + 6 model + factory + seeder
- [x] Phase 23 - `restock_requests` ditambah kolom `supplier_id` + `dss_run_id` (FK hanya di MySQL, SQLite tidak mendukung FK ke tabel existing)
- [x] Phase 24 - Service: `AhpService` (normalisasi, bobot, lambda max, CI/CR), `VikorService` (S/R/Q, ranking, advantage & stability), `RestockRecommendationService`, `ProductRecommendationService` + unit test manual (AHP `[[1,2,4],[1/2,1,2],[1/4,1/2,1]]` -> bobot 4/7,2/7,1/7, CR=0)
- [x] Phase 25 - Middleware role baru (`EnsureUserHasRole`, alias `role`) + route/controller DSS admin & staff + feature test akses per role
- [x] Phase 25 - `app/Services/DssAnalysisService.php` (baru): orkestrasi AHP -> VIKOR -> restock, `saveScores()` (run `draft` otomatis + `dss_supplier_scores`), `storeRun()` (`dss_runs` + payload JSON), `criteriaWithWeights()`, `suppliersWithScores()`
- [x] Phase 25 - Route staff/admin lama kini dibungkus middleware role; 5 route DSS baru (`staff.dss`, `staff.dss.scores.store`, `admin.dss`, `admin.dss.suppliers`, `admin.dss.run`)
- [x] Phase 25 - Test baru: `DssRoleAccessTest` (4), `DssScoreControllerTest` (2), `DssAnalysisControllerTest` (2) - `php artisan test` **68/68 lolos, 454 assertions**
- [x] Phase 26 - UI DSS admin & staff (halaman terpisah, **bukan** section `.dashboard-section` baru di staff) + grup nav "DSS" di sidebar
- [x] Phase 26 - Blade baru: `resources/views/staff/dss.blade.php` (kriteria + bobot AHP, form skor pemasok) dan `resources/views/admin/dss.blade.php` (metrik AHP, ranking VIKOR, rekomendasi restock, riwayat run, tabel skor)
- [x] Phase 26 - Controller dual mode HTML/JSON (`Accept: application/json`); POST form HTML -> redirect + flash, API tetap JSON 201
- [x] Phase 26 - Test baru: `tests/Feature/DssPageRenderTest.php` (6 test) - `php artisan test` **74/74 lolos, 477 assertions**; `view:cache` sukses
- [x] Phase 27 - UI pelanggan: section rekomendasi produk di landing page
- [x] Phase 27 - `app/Http/Controllers/LandingController.php` (baru) menggantikan closure route `/` (route diberi nama `landing`) agar `ProductRecommendationService` bisa di-inject
- [x] Phase 27 - Seksi "Rekomendasi untukmu" di `resources/views/welcome.blade.php` (kartu produk + alasan), CSS `.rec-note`/`.rec-why`, handler `data-need-auth` jadi delegasi `document`
- [x] Phase 27 - Pelanggan -> `recommendFor()` (personal), pengunjung/staff/admin -> `recommendForGuest()` (umum); test `tests/Feature/LandingRecommendationTest.php` (4) - `php artisan test` **78/78 lolos, 490 assertions**
- [x] Phase 28 - Demo seeder (riwayat transaksi 30 hari), verifikasi penuh, section "Bahan Screenshot Laporan DSS" di changelog
- [x] Phase 28 - `database/seeders/DssDemoTransactionSeeder.php` (baru): 30 hari transaksi (2-4/hari, 1-3 detail, 2 produk laris), 4 akun pelanggan demo, stok tidak diubah, FK `transactions.customer_id` dideteksi otomatis (MySQL `users.id` / SQLite `customers.id`), idempoten
- [x] Phase 28 - `database/seeders/DssScoreSeeder.php` (baru): skor pemasok per kriteria pada run draft "Penilaian Pemasok Demo" (harga dari rata-rata penawaran, lead_time/moq dari profil, kualitas/reputasi/layanan dari rating), idempoten
- [x] Phase 28 - Migrasi `2026_10_21_132140_widen_dss_supplier_score_precision.php`: `dss_supplier_scores.score` `decimal(6,2)` -> `decimal(12,2)` (harga beli mentah rupiah)
- [x] Phase 28 - Kedua seeder didaftarkan di `database/seeders/DatabaseSeeder.php` (setelah `DssSeeder`)
- [x] Phase 28 - Test baru: `DssDemoTransactionSeederTest` (3), `DssScoreSeederTest` (3) - `php artisan test` **84/84 lolos, 510 assertions** (baseline 78/490); `pint --dirty` passed
- [x] Phase 28 - Verifikasi MySQL: seed 93 transaksi/30 hari + 30 baris skor; `/admin/dss` 52 KB (ranking VIKOR 5 pemasok, terbaik "Konveksi Bandung Sentosa" = "Solusi Tunggal", 1 produk "Perlu Restock" Rp 850.000); `/staff/dss` 52 KB; landing 35 KB
- [x] Phase 28 - `changelog.md`: entri Phase 28 + section "Bahan Screenshot Laporan DSS"

### Revisi Phase 26 (Tab per Modul DSS + CRUD Pemasok) & Phase 27 (Landing Dua Seksi)
- [x] Revisi 26 - Keputusan: tab per modul dengan **URL tetap** (hash `#dss-*`), controller baru `DssSupplierController`, route JSON lama `admin.dss.suppliers` -> `admin.dss.suppliers.data`
- [x] Revisi 26 - `app/Http/Controllers/DssSupplierController.php` (baru): `store`, `update`, `destroy` (blokir hapus bila sudah dipakai skor/penawaran/rekomendasi/pengajuan), `toggle`, `storeOffer`, `destroyOffer`, `rules()`, `usageOf()`
- [x] Revisi 26 - `app/Http/Controllers/DssAnalysisController.php`: `saveWeights()` (matriks AHP segitiga atas + resiprok), `restockRequest()` (buat pengajuan dari rekomendasi), `summary()` kini mengirim matriks, run terakhir, jumlah pemasok aktif, pengajuan DSS, daftar pemasok, dan produk
- [x] Revisi 26 - `app/Http/Controllers/DssScoreController.php`: `index()` kini mengirim rekomendasi restock + ranking VIKOR + status pengajuan; `restockRequest()` (POST `staff.dss.restock.store`)
- [x] Revisi 26 - `app/Services/DssAnalysisService.php`: `comparisonMatrix()`, `saveComparisons()`, `restockAnalysis()`, `restockRequestFor()`, `latestCompletedRun()`, `activeSupplierCount()`, `employeeIdFor()`; `storeRun()` ikut menyimpan rekomendasi restock
- [x] Revisi 26 - `routes/web.php`: 14 route DSS (`admin.dss.weights.store`, `admin.dss.restock.store`, `staff.dss.restock.store`, `admin.dss.suppliers.store/update/destroy/toggle`, `admin.dss.suppliers.offers.store/destroy`)
- [x] Revisi 26 - `resources/views/admin/dss.blade.php`: 6 tab (Ringkasan, Bobot AHP, Ranking VIKOR, Restock, Pemasok, Riwayat) + 3 kartu ringkasan + form matriks + CRUD pemasok/penawaran + Setujui/Tolak pengajuan
- [x] Revisi 26 - `resources/views/staff/dss.blade.php`: 3 tab (Rekomendasi Restock, Penilaian Supplier, Ranking Supplier read-only) + tombol "Ajukan Restock" + status pengajuan
- [x] Revisi 26 - Bug fix: `redirect()->route(...).'#frag'` menghasilkan string (error "Call to a member function with() on string") -> diganti `->withFragment('frag')` di 3 controller
- [x] Revisi 26 - Test: `tests/Feature/DssSupplierManagementTest.php` (9 test, baru) + `DssPageRenderTest.php` diperluas (11 test) - `php artisan test --filter=Dss` **41/41 lolos, 183 assertions**
- [x] Revisi 27 - `app/Services/ProductRecommendationService.php`: `bestSelling()` (produk paling banyak terjual 90 hari, stok > 0)
- [x] Revisi 27 - `app/Http/Controllers/LandingController.php`: mengirim `$bestSelling` ke view
- [x] Revisi 27 - `resources/views/welcome.blade.php`: landing dipecah jadi **"Rekomendasi untukmu"** (role pelanggan) dan **"Produk Terlaris"** (semua pengunjung) + link nav "Terlaris"
- [x] Revisi 27 - Test: `tests/Feature/LandingRecommendationTest.php` ditulis ulang (5 test) - `php artisan test --filter=LandingRecommendationTest` **5/5 lolos, 19 assertions**
- [x] Tahap akhir - Analisis admin dijalankan 1x ke MySQL -> run #2 `completed` (CR = 0, "Solusi Tunggal", 5 rekomendasi restock tersimpan)
- [x] Tahap akhir - Probe render MySQL: `/admin/dss` 185 KB (6 tab), `/staff/dss` 78 KB, landing tamu 28 KB (4 kartu terlaris) & pelanggan 31 KB (4 rekomendasi + 4 terlaris)
- [x] Tahap akhir - `changelog.md`: entri Phase 26 & 27 diperbarui + section "Bahan Screenshot Laporan DSS" (base URL, akun demo, daftar URL, urutan screenshot)
- [ ] Tahap akhir - **Belum diverifikasi di browser** - pergantian tab DSS (hash), simpan matriks AHP, CRUD pemasok, ajukan/setujui restock, dan dua seksi landing masih perlu dicek manual; test suite penuh & `pint` **tidak dijalankan** sesuai instruksi

### Phase 29 (Fase A - Perbaikan Layout Dashboard)
- [x] Diagnosis bug 1 - halaman terpotong di atas: anchor/hash URL + scroll restoration browser + `.main-content { min-height: 100vh }` di bawah topbar sticky (64px)
- [x] `public/css/la-mamora.css` - `.main-content` -> `min-height: calc(100vh - var(--lm-topbar-height))`, `html.lm-dashboard-root { scroll-padding-top }`, `scroll-margin-top` untuk `.dashboard-section` + `[data-dss-section]`
- [x] `resources/views/layouts/app.blade.php` - `<html class="lm-dashboard-root">` + script reset scroll (`scrollRestoration = 'manual'`, `scrollTo(0, 0)` pada DOMContentLoaded/load/pageshow)
- [x] Diagnosis bug 2 - menu sidebar staff mati di halaman DSS: link hanya berisi hash (`href="#dashboard"`), handler-nya hanya ada di JS dashboard staff, form logout hanya di section `#pengaturan`
- [x] `resources/views/layouts/sidebar.blade.php` - link staff jadi URL penuh `route('staff.dashboard')#<section>`; "Pengaturan" staff = section in-page (di dashboard staff) atau modal Pengaturan (halaman lain); modal Pengaturan dirender untuk staff di luar dashboard staff
- [x] `resources/views/staff/dashboard.blade.php` - `navLinks` hanya section nyata (link modal Profil tidak lagi di-`preventDefault`), hash dibaca dari `href` penuh, `scrollTo(0, 0)` saat pindah section
- [x] `resources/views/admin/dashboard.blade.php`, `admin/dss.blade.php`, `staff/dss.blade.php` - `scrollTo(0, 0)` saat pindah section/modul
- [x] `tests/Feature/DssSidebarNavigationTest.php` (4 test): link sidebar staff/admin, Pengaturan + logout, guard scroll reset, modal Profil
- [x] Verifikasi: `--filter=DssSidebarNavigationTest` 4/4 (43 assertions), `--filter=StaffDashboardTabTest` 5/5 (96), `--filter=Dss` 45/45 (226), `--filter=Dashboard` 7/7 (119) - test suite penuh & `pint` tidak dijalankan
- [ ] Phase 29 - **Belum diverifikasi di browser** - posisi scroll awal, topbar menutupi judul section, dan klik menu sidebar/pengaturan/logout dari halaman DSS

### Phase 30 (Fase B - Alur Approval Restock)
- [x] Diagnosis: `ManagerController::updateStatus()` memakai `redirect()->back()`; header `Referer` tidak membawa fragment hash sehingga admin mendarat di section default dashboard
- [x] `app/Http/Controllers/ManagerController.php` - `updateStatus()` redirect ke `route('admin.dashboard').'#persetujuan-restock'` + flash `success` (nama produk, jumlah, status)
- [x] Aksi lain di halaman terkait sudah memakai hash eksplisit (`#kelola-produk`, `#pengajuan-restock`, `?produk=<id>#pengajuan-restock`, `#dss-restock`) - tidak ada `redirect()->back()` di alur OAS
- [x] `tests/Feature/RestockApprovalRedirectTest.php` (3 test): approve/reject kembali ke menu Persetujuan Restock, stok bertambah hanya saat disetujui, pesan sukses menyebut produk
- [x] Verifikasi: `--filter=RestockApprovalRedirectTest` 3/3 lolos (13 assertions) - test suite penuh & `pint` tidak dijalankan
- [ ] Phase 30 - **Belum diverifikasi di browser** - alur approve/reject dari tabel Persetujuan Restock

### Phase 31 (Fase C - Master Supplier)
- [x] Migration `2026_10_22_090000_add_supplied_categories_to_suppliers_table.php` (kolom `supplied_categories` string 255 nullable) - sudah di-`migrate --force` (MySQL), bukan `migrate:fresh`
- [x] `app/Models/Supplier.php` - `supplied_categories` masuk `$fillable` + helper `suppliedCategoryList()`
- [x] `app/Http/Controllers/SupplierMasterController.php` (baru) - `index()` (pencarian `q` + filter `status` all/active/inactive + prefill `edit`), `store()` (kode otomatis `SUP-xxx`), `update()`, `destroy()` (khusus admin, diblokir bila pemasok terpakai), `authorizeRoles()`, `validated()`, `generateCode()`, `usageOf()`
- [x] `routes/web.php` - grup `role:staff,admin`: `supplier.master` (GET `/master-supplier`), `supplier.master.store` (POST), `supplier.master.update` (PUT), `supplier.master.destroy` (DELETE + `role:admin`)
- [x] `resources/views/supplier/master.blade.php` (baru) - header, metric row, form cari/filter, form tambah/ubah (kategori bahan, lead time, minimal order, rating, status aktif), tabel daftar + edit + hapus
- [x] Sidebar - grup nav "Master Data" berisi "Master Supplier"
- [x] `tests/Feature/SupplierMasterTest.php` (11 test) - akses per role, auto kode, tambah/ubah/hapus, blokir hapus pemasok terpakai, pencarian + filter status, prefill form ubah, dropdown kategori
- [x] Verifikasi: `--filter=SupplierMasterTest` 11/11 lolos (52 assertions) - test suite penuh & `pint` tidak dijalankan
- [ ] Phase 31 - **Belum diverifikasi di browser** - tambah/ubah/hapus pemasok, filter status, dan form edit

### Phase 32 (Fase C - Penawaran Supplier di Form Staff)
- [x] `app/Http/Controllers/StaffController.php` - `index()` mengirim `$suppliers` (hanya `is_active = true`); `storeRestock()` & `storeProduct()` menerima `supplier_id`, `supplier_price`, `supplier_lead_time_days`, `supplier_min_order_qty`; helper `syncSupplierOffer()` (updateOrCreate ke `product_suppliers`)
- [x] Validasi `supplier_id` memakai `Rule::exists('suppliers','id')->where('is_active', true)` - pemasok nonaktif ditolak
- [x] `restock_requests.supplier_id` diisi sesuai pemasok yang dipilih
- [x] `resources/views/staff/dashboard.blade.php` - dropdown "Pilih Supplier" + field Harga Beli/Lead Time/Minimal Order di Form Pengajuan Restock & Form Tambah Master Produk + link "Tambah di Master Supplier"
- [x] `tests/Feature/StaffSupplierOfferTest.php` (6 test) - simpan penawaran, update-or-create, penawaran saat tambah produk, tanpa supplier tidak ada relasi, dropdown hanya aktif, nonaktif ditolak
- [x] Verifikasi: `--filter=StaffSupplierOfferTest` 6/6 (33), `--filter=StaffDashboardTabTest` 5/5 (96), `--filter=Dss` 45/45 (226), `--filter=Dashboard` 7/7 (119), `--filter=Restock` 20/20 (93), `--filter=Product` 8/8 (22), `--filter=Supplier` 27/27 (116)
- [ ] Phase 32 - **Belum diverifikasi di browser** - isi penawaran supplier di form staff dan dampaknya ke rekomendasi DSS

### Phase 33 (Fase C - Ringkasan Test Fase C)
- [x] Seluruh alur Fase C punya test otomatis: CRUD Master Supplier per role (`SupplierMasterTest`, 11 test) + penyimpanan penawaran pemasok (`StaffSupplierOfferTest`, 6 test)
- [x] Cakupan: hak akses admin/staff/pelanggan, auto kode `SUP-xxx`, pencarian `q`, filter `status`, prefill form ubah, guard hapus (403 staff + blokir bila dipakai), `product_suppliers` upsert, `restock_requests.supplier_id`, penolakan pemasok nonaktif
- [x] Verifikasi: `--filter=SupplierMasterTest` 11/11 (52); `--filter=StaffSupplierOfferTest` 6/6 (33); regresi Phase 29-30 hijau; `pint` & test suite penuh tidak dijalankan
- [ ] Phase 33 - **Belum diverifikasi di browser** - seluruh alur Fase C

### Phase 34 (Fase A - UI Pelanggan Selaras dengan Design System)
- [x] `public/css/la-mamora-shop.css` - sidebar/drawer, kartu katalog, filter, detail, favorit, pesanan, modal, chatbot, auth `.lm-auth*`, dark mode `.lm-dark`
- [x] `resources/views/layouts/toko-shell.blade.php` - shell La Mamora (drawer, dark mode, modal, chatbot)
- [x] `resources/views/toko/index.blade.php`, `show.blade.php`, `favorit.blade.php`, `pesanan.blade.php` - extends shell, inline style dibersihkan
- [x] `resources/views/auth/login.blade.php`, `register.blade.php` - desain ulang `.lm-auth*` (tanpa Bootstrap CDN)
- [x] `tests/Feature/CustomerPagesRenderTest.php` (7 test) - katalog/detail/favorit/pesanan/login/register, marker CSS/UI, drawer, tanpa warna biru Shopee
- [x] Token shadow `--lm-shadow-sm/-shadow/-shadow-lg` tersedia di `public/css/la-mamora.css`
- [x] Verifikasi: `--filter=CustomerPagesRenderTest` 7/7 (77); `php artisan view:cache` berhasil

### Phase 35 (Fase A - Keranjang Pelanggan, isolasi ketat)
- [x] Audit keranjang/checkout/favorit/riwayat/profil - tidak ada endpoint yang menerima `user_id` dari request
- [x] `database/migrations/2026_10_21_234447_create_carts_table.php` - `user_id` FK->users cascade, `product_id` FK->products, `quantity`, unique(user_id,product_id)
- [x] `app/Models/Cart.php` - relasi `User::carts()`, `Product::cartItems()`, `resolveRouteBinding()` ter-scope user login
- [x] `app/Http/Controllers/CartController.php` - index/tambah/ubah/hapus/kosongkan/checkout + niat tamu
- [x] Route `toko.cart.*` di middleware `auth` + `role:pelanggan`; item milik user lain -> 404
- [x] Validasi quantity (min 1) dan cek stok sebelum simpan serta saat checkout
- [x] Tombol "Tambah ke Keranjang" di kartu produk, detail, favorit, rekomendasi, landing; tamu -> login -> kembali
- [x] Badge jumlah item di sidebar + topbar lewat `app/View/Composers/CustomerContextComposer.php`
- [x] `resources/views/toko/cart.blade.php` - halaman keranjang design system La Mamora + CSS di `public/css/la-mamora-shop.css`
- [x] Checkout dalam `DB::transaction` + `lockForUpdate()`, buat `transactions` + `transaction_details`, kurangi stok, hapus keranjang
- [x] `app/Services/TransactionCustomerResolver.php` - normalisasi `transactions.customer_id` untuk MySQL (`users.id`) dan SQLite (`customers.id`)
- [x] Isolasi favorit/riwayat transaksi/profil - `favorit()`, `pesanan()`, dan `toggleFavorite()` memakai `Auth::id()`
- [x] Staff/admin tidak melihat tombol keranjang dan mendapat 403 pada route keranjang
- [x] `tests/Feature/CartIsolationTest.php` (16 test), `tests/Feature/CheckoutIsolationTest.php` (6 test), `tests/Feature/FavoriteIsolationTest.php` (5 test)
- [x] Verifikasi: `--filter=CartIsolationTest` 16/16 (57); `--filter=CheckoutIsolationTest` 6/6 (24); `--filter=FavoriteIsolationTest` 5/5 (20); regresi `CustomerPagesRenderTest` 7/7, `LandingRecommendationTest` 5/5, `ProductRecommendationServiceTest` 8/8, `StaffDashboardTabTest` 5/5, `DssDemoTransactionSeederTest` 3/3, `DssRoleAccessTest` 4/4
- [x] Update `changelog.md` + `todo.md`
- [ ] Phase 35 - **Belum diverifikasi di browser** - alur tambah ke keranjang, ubah jumlah, checkout, dan badge

### Phase 36 (Tema Global - token theme-baru.css, dark mode, kontras)
- [x] `public/css/la-mamora.css` - `:root` memakai token `theme-baru.css` (primary lime, background, accent-foreground, chart-1..5, sidebar, logo, radius 1rem, shadow 8/20/.05); nama `--lm-*` lama tetap sebagai alias
- [x] Jembatan Bootstrap 5.3: `--bs-body-bg/-color/-rgb`, `--bs-border-color`, `--bs-border-radius`, `--bs-primary(-rgb)`, `--bs-secondary`, `--bs-link-color`, font Inter
- [x] Mode gelap: satu blok untuk `.dark`, `.lm-dark`, dan `data-bs-theme="dark"` (background/card/accent/sidebar gelap) + token Bootstrap gelap
- [x] `resources/views/layouts/theme.blade.php` - modul `window.lmTheme`, toggling `.dark` + `data-bs-theme`, `localStorage('lm-theme')`, default terang, binding `[data-theme-toggle]`, tanpa kedip
- [x] Tombol ganti tema di topbar dashboard admin/staff, topbar toko, dan navbar landing
- [x] Font Inter + JetBrains Mono dimuat di landing, toko, login, register, dan dashboard
- [x] Sidebar admin/staff memakai token sidebar (aktif lime + teks hitam); kartu/tabel/form/modal/dropdown memakai token card/border/input/muted/ring/popover
- [x] Logo (`.lm-mark`, `.logo`, `.avatar`) memakai `--lm-logo-ring` / `--lm-logo-letter` kontras dua mode
- [x] Grafik Chart.js admin memakai token `--lm-chart-1..5`; hapus warna Shopee/Bootstrap lama di view
- [x] `tests/Feature/ThemeToggleRenderTest.php` (3 test) - tombol+modul tema di semua layout, token & mode gelap di stylesheet
- [x] Verifikasi: `--filter=ThemeToggleRenderTest` 3/3 (26); regresi 43/43 (`CustomerPagesRenderTest`, `StaffDashboardTabTest`, `DssPageRenderTest`, `LandingRecommendationTest`, `DssSidebarNavigationTest`, `SupplierMasterTest`)
- [x] Update `changelog.md` + `todo.md`
- [ ] Phase 36 - **Belum diverifikasi di browser** - toggle terang/gelap, kontras tombol/badge/sidebar, dan grafik

### Phase 37 (Logo Resmi La Mamora - inline SVG bertoken)
- [x] `resources/views/layouts/lamamora-logo.blade.php` - komponen inline SVG logo resmi tanpa metadata c2pa; `height` (default 40) + `class`
- [x] `public/css/la-mamora.css` - token `--lm-logo-ring/--lm-logo-letter/--lm-logo-bg`; rule `.sidebar-brand .lm-logo-svg` (34px)
- [x] `public/css/la-mamora-shop.css` - ukuran `.brand .lm-logo-svg` (36px) dan `.lm-auth-brand .lm-logo-svg` (40px)
- [x] Logo dipasang di navbar landing, sidebar dashboard, sidebar toko, kartu login & register
- [x] Verifikasi: `--filter=ThemeToggleRenderTest` 4/4; regresi 47/47; `vendor/bin/pint` bersih

### Phase 38 (Perbaikan UI Pelanggan - drawer, filter server, keranjang, pembayaran simulasi)
- [x] **A.** `toko-shell.blade.php` + `la-mamora-shop.css` - sidebar jadi drawer fixed semua layar (X, overlay, Esc, klik item, lock scroll); menu Profil buka modal akun
- [x] **B.** `TokoController::index` + `resolveCatalogFilters()` (q, kategori/jenis/ukuran/warna[], min/max, urut `terbaru|termurah|termahal|terlaris` via `withSum`); view panel lipat + chip filter aktif + Reset; `Product::transactionDetails()`
- [x] **B.** `CategorySeeder` idempoten (Atasan, Celana, Outer, Bahan kain, Aksesori) didaftarkan di `DatabaseSeeder`
- [x] **C.** Tombol "+ Keranjang" hanya membuka modal konfirmasi; item tersimpan hanya setelah POST ber-CSRF ke `toko.cart.add`/`intent`
- [x] **D.** Migrasi `payment_methods` + `transaction_payments`; model `PaymentMethod`/`TransactionPayment`; `PaymentService`; `PaymentMethodSeeder` (Kartu, ATM/VA, E-Money, Direct Debit, Gerai, Tunai)
- [x] **D.** `Transaction::payment()` + `payment_method_label`; modal katalog/detail & checkout memakai akordeon metode; server menolak kode tidak aktif, simpan kode singkat, VA prefix per bank / kode gerai + `expires_at` 24 jam, `phone_last4`
- [x] **D.** Keamanan: nomor kartu/CVV/nomor rekening tanpa atribut `name`, tidak dikirim/disimpan/di-log; simulasi tanpa payment gateway
- [x] Riwayat: metode lama (E-Wallet, M-Banking, Transfer Bank, Cash) tampil via fallback `PaymentMethod::labelFor()`; badge "Menunggu Pembayaran"
- [x] `tests/Feature/CatalogFilterTest.php` (5), `CartAddFlowTest.php` (4), `CheckoutPaymentTest.php` (8); `CheckoutIsolationTest` diperbarui ke kode valid
- [x] Verifikasi: `--filter=CartIsolationTest|CheckoutIsolationTest|CatalogFilterTest|CartAddFlowTest|CheckoutPaymentTest|CustomerPagesRenderTest|FavoriteIsolationTest` 51/51; `pint`/suite penuh tidak dijalankan
- [ ] Phase 38 - **Belum diverifikasi di browser** - drawer, panel filter, akordeon pembayaran terang/gelap

### Penutup
- [x] Update section "Bahan Screenshot Laporan DSS" di `changelog.md` bila ada halaman baru (Master Supplier, form restock, keranjang)
- [ ] Commit perubahan setelah diminta
