# Changelog - Laravel TPS & OAS

## 2026-09-17 - Phase 1: Role Refactoring & Database Update
- Renamed `app/Http/Controllers/KasirController.php` â†’ `app/Http/Controllers/StaffController.php`
- Renamed `resources/views/kasir/` â†’ `resources/views/staff/`
- Updated `routes/web.php`: `/kasir/` â†’ `/staff/`, controller references updated
- Updated `resources/views/staff/dashboard.blade.php`: navbar text, form action route
- Created migration `2026_09_17_145733_update_role_kasir_to_staff.php` - updated user roles from 'kasir' to 'staff' in DB
- Ran `php artisan migrate` successfully

## 2026-09-17 - Phase 2: Single Portal Login
- Updated `resources/views/welcome.blade.php`: removed role-specific navbar buttons, single "Masuk ke Aplikasi" login button
- Updated `app/Http/Controllers/AuthController.php`: role redirection - pelangganâ†’/toko, staffâ†’/staff/dashboard, adminâ†’/admin/dashboard

## 2026-09-17 - Phase 3: Fitur Transaksi Pembeli (Ditenhapi/Major Enhancement)
- Added route `POST /toko/buy/{product_id}` named `toko.buy`
- Created `app/Models/Transaction.php` - New Eloquent model for transactions table
  - Kolom: `id`, `customer_id` (FK â†’ users), `employee_id` (FK â†’ users, NULLABLE), `date` (datetime), `total` (decimal 10,2), `status` (enum: pending/processing/completed/cancelled)
  - Relasi: `customer()` â†’ User, `employee()` â†’ User
- Updated `app/Http/Controllers/TokoController.php`:
  - Enhanced `buyProduct()` method menerima parameter quantity dari form input (default 1)
  - Validasi stok produk sebelum pembelian
  - Kurangi stok produk: `$product->stock -= $quantity`
  - Simpan record transaksi ke database melalui `Transaction::create()`:
    * `customer_id` = auth()->user()->id
    * `employee_id` = NULL (transaksi mandiri pelanggan)
    * `date` = now()
    * `total` = $product->price Ã— $quantity (menggunakan kolom `total`, bukan `total_payment`)
    * `payment_method` = input dari user (default 'online')
    * `status` = 'completed' (gunakan enum yang sudah ada, bukan 'success')
  - Kembali dengan flash message sukses beserta total bayar
- Updated `resources/views/toko/index.blade.php`:
  - Tambah input quantity (Bootstrap form-control-sm, min=1, default value 1)
  - Setiap produk sekarang memiliki field quantity sebelum tombol "Beli Sekarang"
  - Menambahkan Modal Konfirmasi Pembelian Bootstrap 5 dengan:
    * Header gelap/biru (bg-primary text-white)
    * Rincian Produk, Jumlah Qty, Total Harga
    * Dropdown Pilihan Metode Pembayaran ('E-Wallet', 'M-Banking', 'Transfer Bank', 'Cash')
    * Tombol Batal dan Konfirmasi Pembelian

## 2026-09-17 - Phase 3.5: Perbaikan Foreign Key & Verifikasi Database
- **Migrasi `2026_09_17_155756_fix_transactions_foreign_keys_and_columns`** berhasil dijalankan
- **Perbaikan FK `customer_id`**: Direpoint dari `customers` table â†’ `users` table via raw SQL `ALTER TABLE`
- **Perbaikan FK `employee_id`**: Direpoint dari `employees` table â†’ `users` table, dibuat **NULLABLE** untuk transaksi pelanggan mandiri
- **Kolom `total`**: Tersedia dengan tipe `decimal(10,2)` dan nilainya diisi `harga Ã— quantity` di controller
- **Kolom `payment_method`**: Tersedia, diisi berdasarkan input user dari modal (default 'online')
- **Kolom `status`**: Tersedia dengan enum nilai `pending/processing/completed/cancelled`, diisi `'completed'` di controller
- Verifikasi: `php artisan migrate:status` menunjukkan migration `2026_09_17_155756_fix_transactions_foreign_keys_and_columns` status **Ran**
- Model `Transaction` (`app/Models/Transaction.php`) diupdate:
  - `$fillable` mencakup: `customer_id`, `employee_id`, `date`, `total`, `payment_method`, `status`
  - `$casts` mencakup: `total` => `decimal:2`
  - Relasi `customer()` dan `employee()` ke `User::class` melalui kolom foreign key
- Semua struktur kolom dan relasi sesuai requirement tanpa perlu `migrate:fresh`

## 2026-09-17 - Phase 4: Polishing UI/UX Bootstrap 5
- `resources/views/staff/dashboard.blade.php`: 2-column layout (Sidebar kiri + Konten kanan), monitoring stok realtime dengan badge warna (merah <= 5, hijau > 5), navbar dark, navigasi sidebar
- `resources/views/admin/dashboard.blade.php`: 2-column layout serupa, menu sidebar: Monitoring Stok Realtime, Persetujuan Restock (Approval), tabel approval dengan tombol Approve/Reject
- `resources/views/toko/index.blade.php`: Modal Konfirmasi Pembelian Bootstrap 5, dropdown payment method, input quantity, badge stok warna
- `resources/views/auth/login.blade.php`: already styled with Bootstrap 5 card layout
- `resources/views/auth/register.blade.php`: already styled with Bootstrap 5 card layout

## 2026-09-17 - Phase 5: Task Tracking
- Updated `todo.md`: checked off all 10 tasks including Semua Fitur Baru
- Updated `changelog.md`: comprehensive log of all file changes and functions added

## 2026-09-23 - Phase 6: Sidebar UI Refactor & KMS Chatbot Module
### Sidebar UI Refactor (SantriKoding Style)
- Created master layout `resources/views/layouts/app.blade.php` with shared sidebar structure, CDN Bootstrap 5 & Bootstrap Icons
- Created shared sidebar partial `resources/views/layouts/sidebar.blade.php` with:
  - Clean white background (`bg-white border-end shadow-sm`)
  - Brand header with logo + role badge (Staff/Admin)
  - Grouped navigation with UPPERCASE section labels (UTAMA, OAS & STOK, AKUN)
  - Active state: `bg-primary bg-opacity-10 text-primary fw-semibold rounded-3`
  - Inactive state: `text-secondary hover-bg-light rounded-3`
  - Bootstrap Icons: `bi-speedometer2`, `bi-box-seam`, `bi-chat-dots`, `bi-box-arrow-right`
  - Footer with POST logout form (`btn btn-outline-danger w-100`)
  - Profile & Settings modals
- Refactored `resources/views/staff/dashboard.blade.php` to extend master layout
- Refactored `resources/views/admin/dashboard.blade.php` to extend master layout

### KMS Chatbot FAQ Automation Module
- Created migration `2026_09_23_080239_create_chatbots_table.php`:
  - Table `chatbots` with `id`, `queries` (text), `replies` (text), timestamps
- Created Model `app/Models/Chatbot.php` with `$fillable = ['queries', 'replies']`
- Created Seeder `database/seeders/ChatbotSeeder.php` with 8 FAQ entries:
  - Jam operasional, Cara beli, Metode pembayaran, Pengembalian/Retur, Pengiriman, Ukuran/Size guide, Kontak CS, Promo/Diskon
- Ran `php artisan migrate` and `php artisan db:seed --class=ChatbotSeeder` successfully
- Created Controller `app/Http/Controllers/ChatbotController.php`:
  - Method `message(Request $request)` with LIKE-based query matching
  - Returns matched reply or default fallback message
- Added route `POST /chatbot/message` â†’ `ChatbotController@message` (named `chatbot.message`)
- Added Floating Chatbot Widget to `resources/views/toko/index.blade.php`:
  - FAB button (bottom-right) with `bi-chat-dots-fill` icon
  - Custom floating card (position: fixed) with chat window
  - Message area with bot/user bubbles, typing indicator
  - Input field + send button with AJAX (fetch) to `/chatbot/message`
  - Quick action buttons for common queries
  - Smooth animations, responsive design, keyboard accessible

- Updated `todo.md`: added and checked off 2 new tasks
- Updated `changelog.md`: added Phase 6 comprehensive entry

## 2026-09-30 - Phase 7: Refactor Dashboard & Navigasi Staff (Tab-Based)
### Model & Backend
- Created `app/Models/Category.php` - model untuk tabel `categories` (`id`, `name`, timestamps) + relasi `products()` â†’ `hasMany(Product::class)`
- Updated `app/Models/Product.php`: ditambahkan relasi `category()` â†’ `belongsTo(Category::class)`
- Updated `app/Http/Controllers/StaffController.php` â†’ `index()`:
  - `$products` = `Product::with('category')->orderBy('id')->get()` (eager loading kategori)
  - `$topProducts` = 5 produk pertama (untuk ringkasan dashboard)
  - `$criticalThreshold` = `5` (ambang stok kritis, dikirim ke view agar badge & widget konsisten)
  - `$totalProduk`, `$stokKritis` (`stock <= 5`), `$restockPending` (status `pending`) untuk widget statistik
  - `$recentRestocks` = 5 pengajuan terbaru (ringkasan dashboard)
  - `$pendingRestocks` = **hanya** status `pending` (tab Pengajuan Restock)
  - `$allRestocks` = seluruh riwayat semua status (tab Riwayat Restock)
  - `$restockRequests` tetap dipertahankan sebagai alias `$allRestocks` (backward compatible)

### Sidebar & Logout
- `resources/views/layouts/app.blade.php`:
  - **Dihapus** blok `.sidebar-footer` berisi form POST logout (tombol Logout merah di bawah sidebar)
  - **Dihapus** CSS `.sidebar-footer` dan `.sidebar-footer .btn` (dead code)
- `resources/views/layouts/sidebar.blade.php`:
  - Tombol Logout dihapus dari bawah sidebar (sekarang hanya lewat menu Pengaturan)
  - Menu **Dashboard** (role staff) â†’ `data-bs-target="#dashboard"`, href `#dashboard`
  - Menu **Monitoring Stok** â†’ href & target `#monitoring-stok` (sebelumnya `#stok-produk`)
  - Menu **Pengajuan Restock** â†’ href & target `#pengajuan-restock`
  - Menu **Riwayat Restock** â†’ href & target `#riwayat-restock`
  - Menu **Pengaturan** â†’ kondisi per role: staff memakai section `#pengaturan`, role lain tetap membuka modal `#settingsModal`
  - Atribut `data-bs-toggle="tab"` sengaja **dihapus** dari link staff; perpindahan section diurus JS manual agar tidak ada dua handler (Bootstrap data-API + JS)
  - Semua atribut tab hanya di-render untuk role `staff` agar dashboard admin & pelanggan tidak berubah

### Dashboard Staff (5 section)
- `resources/views/staff/dashboard.blade.php` dipecah menjadi 5 section (`<div class="dashboard-section">`) di dalam wrapper `#staffDashboard`; hanya `#dashboard` yang tampil awal, section lain memakai `d-none`
  1. **`#dashboard`** (default aktif) â€” ringkasan singkat:
     - 3 widget statistik: Total Produk, Stok Kritis, Restock Pending
     - "Ringkasan Stok Produk" (maks 5 produk) dengan kategori, varian, harga, sisa stok
     - "Ringkasan Pengajuan Terbaru" (tabel 5 pengajuan terakhir + badge status)
  2. **`#monitoring-stok`** â€” tabel detail produk lengkap: `ID | Nama Produk | Kategori | Type | Size | Color | Price | Stock | Status`, plus input **Search/Filter client-side** (`#search-product`, info jumlah hasil di `#search-product-info`) untuk cari nama/ukuran/warna produk dengan baris "Produk tidak ditemukan"
  3. **`#pengajuan-restock`** â€” atas: Form Pengajuan Restock Barang (OAS); bawah: "Daftar Pengajuan Menunggu Keputusan (Pending Only)" yang **hanya** menampilkan status `pending`
  4. **`#riwayat-restock`** â€” tabel lengkap seluruh riwayat (Pending/Approved/Rejected) dengan badge `bg-warning text-dark` / `bg-success` / `bg-danger`, kolom ID, Tanggal, Produk + varian, Jumlah, Status, Catatan, serta rekap jumlah per status di header
  5. **`#pengaturan`** â€” Card "Pengaturan & Keamanan Akun" berisi form POST `route('logout')` + `@csrf` dengan tombol `btn btn-outline-danger` ("Logout Akun"), berdampingan dengan Card "Informasi Akun"
- Badge status stok 3 tingkat: `stock == 0` â†’ **Stok Habis** (danger), `stock <= 5` â†’ **Stok Kritis** (warning), selainnya **Stok Aman** (success)
- JS (`@push('scripts')`) â€” navigasi section manual, tanpa reload:
  - `activateSection(id)`: sembunyikan section aktif (`d-none`), tampilkan section yang diklik, sinkronkan kelas `.active` pada nav sidebar
  - `event.preventDefault()` pada klik menu sidebar agar browser tidak melakukan anchor jump bawaan
  - `history.replaceState` untuk memperbarui hash tanpa menambah riwayat browser
  - `location.hash` dibaca saat halaman dimuat **dan** saat event `hashchange`; hash tak dikenal/tidak ada di-fallback ke `dashboard`
  - Menutup sidebar otomatis di layar mobile setelah memilih menu
  - Filter search produk client-side (case-insensitive, filter di browser tanpa reload; realtime toggle baris + baris "Produk tidak ditemukan" + info jumlah hasil)

### Verifikasi
- `php artisan view:clear` / `view:cache` sukses
- `./vendor/bin/pint --test` dan `php artisan test` dijalankan tanpa error
- `tests/Feature/StaffDashboardTabTest.php`: 2 test / 38 assertion memverifikasi 5 section, tabel pending-only tidak memuat Approved/Rejected, riwayat memuat 3 status, tepat 1 form logout, dan sidebar role admin/pelanggan tetap memakai modal

## 2026-09-30 - Phase 8: Fitur Tambah Produk Baru (Staff)
- `routes/web.php`: tambah route `POST /staff/products` â†’ `StaffController@storeProduct` (named `staff.products.store`), di dalam grup `auth`
- `app/Http/Controllers/StaffController.php`:
  - Import `App\Models\Category`; `index()` kini mengirim `$categories = Category::orderBy('name')->get()` untuk dropdown kategori
  - Method baru `storeProduct()` dengan validasi:
    * `category_id` â†’ `required|exists:categories,id`
    * `name` â†’ `required|string|max:100`
    * `type` â†’ `nullable|string|max:50`
    * `size` â†’ `nullable|string|max:10`
    * `color` â†’ `nullable|string|max:30`
    * `price` â†’ `required|numeric|min:0`
    * `stock` â†’ `nullable|integer|min:0` (default 0)
  - `max:` mengikuti panjang kolom di skema tabel `products`; `type`/`size`/`color` opsional sesuai kolom yang `nullable`
  - Redirect ke `route('staff.dashboard') . '#monitoring-stok'` (bukan `back()`, karena browser membuang fragment dari header `Referer`) + flash message sukses
- `resources/views/staff/dashboard.blade.php` (section `#monitoring-stok`):
  - Tombol `btn btn-primary` "Tambah Produk Baru" di atas tabel, sejajar dengan input search
  - Modal Bootstrap `#modalTambahProduk`: form berisi **Category** (dropdown dari `$categories`), **Nama Produk**, **Type** (text + `<datalist>` Atasan/Bawahan/Aksesoris), **Size**, **Color**, **Harga** (number `step="0.01"`), **Stok Awal** (default 0)
  - Alert error + `old(...)` pada seluruh field + `@error` per field agar input staff tidak hilang saat validasi gagal
  - Modal ditempatkan **di luar** wrapper `#staffDashboard` (wajib: section yang tidak aktif memakai `d-none` â†’ `display:none !important`, yang membuat `.modal` di dalamnya tak terlihat meski sudah `.show`)
  - JS: `data-reopen` + `bootstrap.Modal.getOrCreateInstance(...).show()` membuka kembali modal otomatis setelah redirect validasi gagal
- Dropdown Pengajuan Restock tidak perlu diubah â€” sudah meng-loop `$products`, dan `storeProduct()` me-redirect sehingga `index()` mengambil ulang data produk terbaru

## 2026-09-30 - Phase 9: Category Fleksibel & Shortcut Produk Baru
- `app/Http/Controllers/StaffController.php` â†’ `storeProduct()`:
  - Validasi `category_id|exists:categories,id` diganti menjadi `category|required|string|max:50` (nama kategori, `max:50` mengikuti kolom `categories.name`)
  - `Category::firstOrCreate(['name' => trim($data['category'])])` â€” staff boleh memilih dari datalist **atau** mengetik kategori baru; kategori otomatis dibuat bila belum ada, lalu `->id` dipakai untuk `products.category_id`
  - Redirect memakai hidden field `_return` (section asal staff) yang di-whitelist terhadap 5 ID section yang valid, default `monitoring-stok`
- `resources/views/staff/dashboard.blade.php`:
  - Field **Category** pada `#modalTambahProduk` diubah dari `<select>` kaku menjadi `<input type="text" list="category-list">` + `<datalist id="category-list">` berisi kategori yang sudah ada; `@error('category_id')` â†’ `@error('category')`; ditambahkan helper "Kategori yang belum ada akan dibuat otomatis"
  - Hidden input `_return` (`#product-form-return`) pada form modal
  - JS: event `show.bs.modal` mengisi `_return` dengan ID section yang sedang aktif (`document.querySelector('.dashboard-section:not(.d-none)')`), sehingga setelah simpan staff kembali ke tab asalnya â€” `#pengajuan-restock` bila modal dibuka dari tab itu, `#monitoring-stok` bila dari tab Monitoring
  - Tab `#pengajuan-restock`: tombol kecil `btn-outline-primary` "+ Produk Baru" di sebelah kanan label "Pilih Produk" yang membuka `#modalTambahProduk` (`type="button"` agar tidak ikut submit form restock)
- Catatan teknis: `categories.name` dan `products.name` belum punya unique index, sehingga `firstOrCreate` aman untuk pemakaian normal tetapi tetap race-prone bila dua staff mengetik kategori identik bersamaan

## 2026-09-30 - Phase 10: Tambah Produk Inline + Dropdown Select Bertingkat
Pemuatan produk dipindah dari tab Monitoring Stok ke tab Pengajuan Restock agar alur "daftar barang â†’ buat barang â†’ ajukan restock" berada dalam satu halaman.

- Penataan lokasi (semua di `resources/views/staff/dashboard.blade.php`):
  - Tombol "Tambah Produk Baru" dihapus dari header Monitoring Stok (kolom search dilebarkan ke `col-lg-5`)
  - Tombol "+ Produk Baru" dihapus dari header label "Pilih Produk" di Form Pengajuan Restock
  - Seluruh modal `#modalTambahProduk` (beserta hidden `_form`, `_return`, dan atribut `data-reopen`) dihapus
  - Card baru **"Tambah Master Produk Baru"** dipasang di bawah Form + Tabel Pengajuan Restock, berisi form inline `POST staff/products.store` dengan blok `@if($errors->any())` dan seluruh input produk
- UI Kategori & Type â€” dropdown select bertingkat (menggantikan `<datalist>` bawaan browser):
  - `<select id="category-select">` memuat kategori yang sudah ada plus opsi teratas `+ Tambah Kategori Baru...`; `<select id="type-select">` memuat Atasan/Bawahan/Aksesoris plus `+ Tambah Type Baru...`
  - `<select>` sengaja **tanpa** atribut `name` dan hanya berfungsi sebagai kendali UI; nilainya selalu dibawa oleh `<input name="category">` / `<input name="type">` di bawahnya, sehingga kontrak backend tetap berupa satu string nama
  - Memilih opsi `__baru__` memunculkan (`d-none` dilepas) input teks kecil di bawah dropdown; memilih kategori/type yang ada menyembunyikannya dan menyalin nilai terpilih ke input
  - State awal setelah validasi gagal dihitung di Blade (`$isNewCategory` / `$isNewType`): bila `old()` tidak ada di daftar, select disetel ke `+ Tambah ...` dan input teks ditampilkan
  - Implementasi JS tetap vanilla (`setupSelectBertingkat`) â€” tanpa jQuery/Select2 agar konsisten dengan gaya halaman ini
- Auto-select produk baru â€” `app/Http/Controllers/StaffController.php`:
  - Logika whitelist `_return` dihapus; hasil `Product::create()` disimpan ke `$product`
  - Redirect dipatok ke section Pengajuan Restock dengan penanda `?produk=<id>#pengajuan-restock`
  - Blade menandai option tersebut dengan `@selected(request()->integer('produk') === $product->id)`, sehingga produk yang baru dibuat langsung terpilih di dropdown "Pilih Produk"
- Perilaku tak terduga yang ditangani: alert sukses berada di paling atas halaman sedangkan form produk berada di bawah tabel, sehingga ditambahkan `window.scrollTo({ top: 0 })` yang dipicu hanya saat ada flash success atau error validasi
- `tests/Feature/StaffDashboardTabTest.php` diperbarui (belum dijalankan): assert card + select bertingkat hadir, assert modal/`_return`/`data-reopen` hilang, plus dua test baru untuk `firstOrCreate` kategori baru (dengan redirect + auto-select) dan idempotensi kategori lama

## 2026-09-30 - Phase 11: Produk Baru Selalu Terdaftar dengan Stok 0
Field "Stok Awal" dihapus dari Form Tambah Master Produk Baru. Stok tidak lagi boleh diisi bebas saat pendaftaran; satu-satunya cara menambah stok adalah pengajuan restock yang disetujui manager.

- `resources/views/staff/dashboard.blade.php`:
  - Blok `Stok Awal` (label, `<input name="stock">`, `@error('stock')`) dihapus dari card "Tambah Master Produk Baru"
  - `Size` dan `Harga` dilebarkan dari `col-6 col-md-3` menjadi `col-12 col-md-6` supaya tetap mengisi baris penuh
  - Teks lama "Kosongkan atau isi 0 bila produk belum ada stoknya." diganti catatan di samping tombol Simpan: `*Catatan: Produk baru akan didaftarkan dengan stok 0. Untuk mengisi stok fisik, silakan ajukan restock setelah produk disimpan.*`
- `app/Http/Controllers/StaffController.php` â†’ `storeProduct()`:
  - Aturan validasi `'stock' => 'nullable|integer|min:0'` dihapus
  - `Product::create()` memakai `'stock' => 0` secara hardcoded, sehingga nilai `stock` pada request diabaikan sepenuhnya walau tetap dikirim
- `tests/Feature/StaffDashboardTabTest.php` (belum dijalankan): `stock` sengaja dikirim `5` pada payload untuk membuktikan backend mengabaikannya (`assertSame(0, $product->stock)`), plus assert `name="stock"` dan `Stok Awal` tidak lagi muncul di HTML
- Catatan: kolom `products.stock` bermigrasi sebagai `$table->integer('stock')->default(0)` (NOT NULL), jadi nilai hardcoded `0` konsisten dengan skema

## 2026-09-30 - Phase 12: Redirect Pengajuan Restock & "Riwayat Stok" Audit Trail
Tab "Riwayat Restock" diubah menjadi "Riwayat Stok", dan isinya digabung menjadi satu audit trail yang memuat dua jenis aktivitas stok.

- `app/Http/Controllers/StaffController.php`:
  - `storeRestock()`: `redirect()->back()` diganti redirect eksplisit `->to(route('staff.dashboard') . '#pengajuan-restock')`. Sebelumnya fragment hash ikut hilang saat `back()`, sehingga staff selalu mendarat di tab Dashboard.
  - `index()`: menambah variabel `$riwayatStok` â€” `Collection` hasil `merge()` antara aktivitas **Pengajuan Restock** (dari `$allRestocks`) dan **Produk Baru** (dari `$products`), diurutkan `->sortByDesc('tanggal')->values()`. Varian baru ikut ditambahkan ke `compact()`.
  - Detail baris produk baru memakai nilai stok sebenarnya: `Master Data Terdaftar (N Pcs)`, bukan angka 0 hardcode, agar produk lama yang sudah punya stok tidak tampil salah. Produk baru sendiri selalu 0 sejak Phase 11.
- `resources/views/layouts/sidebar.blade.php`:
  - Link staff: `href`/`data-bs-target` `#riwayat-restock` â†’ `#riwayat-stok`, teks "Riwayat Restock" â†’ "Riwayat Stok".
  - Link admin: `href` `admin.dashboard#riwayat-restock` â†’ `admin.dashboard#riwayat-stok`, teks diubah sama.
- `resources/views/staff/dashboard.blade.php` â€” section `#riwayat-restock` â†’ `#riwayat-stok`, judul card â†’ "Riwayat Aktivitas Stok", tabel 6 kolom diganti menjadi `Tanggal | Tipe Aktivitas | Produk | Jumlah | Status | Keterangan`:
  - Tipe Aktivitas: badge `Produk Baru` (info) atau `Pengajuan Restock` (secondary).
  - Jumlah: `10 pcs` untuk restock, `â€”` untuk produk baru.
  - Status: `Terdaftar` (info) untuk produk baru, Pending/Approved/Rejected untuk restock.
  - Badge ringkasan di header dipertahankan, ditambah `Produk Baru: N`.
- `tests/Feature/StaffDashboardTabTest.php` diperbarui (belum dijalankan): 6 referensi `riwayat-restock` â†’ `riwayat-stok`, judul card, assert badge `Produk Baru: 2` + `Master Data Terdaftar (N Pcs)`, assert jumlah baris per tipe (2 produk + 3 restock), dan assert link sidebar admin.

## 2026-09-30 - Phase 13: Perbaikan Fatal `format() on null`
Error "Call to a member function format() on null" muncul di tabel Riwayat Stok saat sebagian record tidak memiliki `created_at` (data lama/import).

- `resources/views/staff/dashboard.blade.php`:
  - Tabel Riwayat Stok: `{{ $row['tanggal']->format(...) }}` â†’ `{{ $row['tanggal']?->format('d/m/Y H:i') ?? '-' }}` (null-safe operator + fallback `"-"`)
  - "Ringkasan Pengajuan Terbaru" (baris ~131) punya pola yang sama dan ikut diperbaiki
- `resources/views/admin/dashboard.blade.php` â€” tabel persetujuan punya pola identik, ikut diperbaiki Thoughiefungsi di luar halaman staff
- `app/Http/Controllers/StaffController.php` â†’ `index()`:
  - Fallback tanggal: `'tanggal' => $req->created_at ?? $req->updated_at` (dan sama untuk produk) agar tetap ada bila `created_at` null
  - `sortByDesc('tanggal')` diganti `sortByDesc(fn ($row) => $row['tanggal']?->getTimestamp() ?? 0)` â€” mengurutkan key numerik, record tanpa timestamp otomatis mendarat di akhir daftar

- `tests/Feature/StaffDashboardTabTest.php` (belum dijalankan): test baru `test_riwayat_stok_tetap_render_untuk_record_tanpa_timestamp` menyisipkan produk + restock dengan `created_at`/`updated_at` null lalu memverifikasi halaman tetap `assertOk()`

### Temuan yang belum diperbaiki (di luar scope)
- **Link sidebar admin adalah anchor mati.** `resources/views/admin/dashboard.blade.php` hanya satu halaman datar 83 baris tanpa section ber-id, sehingga `#stok-realtime`, `#persetujuan-restock`, dan `#riwayat-stok` semuanya tidak menuju ke mana-mana. Rename admin di atas hanya untuk konsistensi penamaan, tidak memperbaiki navigasi.
- **Tabel riwayat tanpa paginasi.** `$riwayatStok` digabung dan diurutkan di memory atas seluruh `$products` + `$allRestocks`; akan berat bila data bertambah banyak.


## 2026-09-30 - Phase 14: Katalog Shoplytic Modern + Upload Foto Produk

Dua pekerjaan yang digabung: rewrite halaman katalog pelanggan menjadi UI grid modern, dan penambahan fitur foto produk pada alur master produk staff.

### A. Upload Foto Produk

- `database/migrations/2026_09_30_120000_add_image_to_products_table.php` (baru): menambah kolom `image` nullable setelah `color` ke tabel `products`. Rollback `dropColumn('image')`. **Sudah dijalankan** via `php artisan migrate --force` (hanya baris ini, tidak ada `migrate:fresh`).
- `app/Models/Product.php`: `'image'` ditambahkan ke `$fillable`, plus accessor `getImageUrlAttribute(): ?string` yang mengembalikan `asset($this->image)` bila terisi, `null` bila kosong.
- `app/Http/Controllers/StaffController.php`:
  - `storeProduct()`: validasi baru `'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'`.
  - Penyimpanan file: folder `public/images/produk` dibuat otomatis bila belum ada (`mkdir(..., recursive: true)`), nama file diacak `time() . '_' . Str::random(8) . '.' . $ext` agar tidak bentrok dan tidak membocorkan nama asli, lalu path relatif `images/produk/<file>` disimpan ke kolom `image`. Sengaja memakai disk publik langsung, **bukan** `Storage::disk('public')`, supaya tidak perlu `storage:link`.
  - Import `Illuminate\Support\Str` ditambahkan.
- `resources/views/staff/dashboard.blade.php`:
  - Form `formTambahProduk` diberi `enctype="multipart/form-data"`.
  - Field baru "Foto Produk" (`<input type="file" name="image">`, `accept="image/jpeg,image/png,image/webp"`) + `form-text` penjelasan format/ukuran + blok `@error('image')`. Form tetap tidak punya input stok (Phase 11).

### B. Rewrite Katalog Pelanggan (`resources/views/toko/index.blade.php`)

- View lama (Bootstrap, 474 baris) diganti total dengan layout Shoplytic: sidebar kiri (brand, nav, logout `@csrf`), topbar dengan breadcrumb + dual search, panel filter, dan grid produk.
- **Server-rendered, tanpa mock**: grid memakai `@forelse($products as $product)`, tiap card punya `data-nama` (lowercase gabungan name/kategori/type/color), `data-harga` (integer), dan `data-kategori`. Tidak ada lagi array `const products = [...]`.
- **Filter 100% client-side** di `applyFilter()`: hanya menyetel `card.style.display` dan memperbarui counter `#count` serta pesan `#noMatch`. Komponen: checkbox kategori (dari DB, plus "Semua Kategori"), checkbox rentang harga (`all`, `0-100000`, `100000-250000`, `250000-500000`, `500000-1000000`), dan dual-handle slider custom 0-1.000.000 dengan bubble Rupiah. Tombol `Terapkan` dan `Reset` (reset mengembalikan seluruh state ke default).
- Dua input search (`#globalSearch` di topbar dan `#search` di head grid) disinkronkan dua arah lewat handler `onSearch`.
- Accordion buka/tutup untuk blok "Kategori" dan "Harga Produk" memakai `data-acc` + `style.display`.
- Dark mode: tombol `#theme` menukar nilai CSS variable (`--bg`, `--card`, `--ink`, `--muted`, `--line`, `--field`, `--tile`, `--accent-soft`) via inline `documentElement.style`, tanpa reload dan tanpa `localStorage`.
- Harga diformat Rupiah: `Rp {{ number_format($product->price, 0, ',', '.') }}` di server dan `Number(n).toLocaleString('id-ID')` di JS. Badge stok memakai kelas `.out` (merah) saat `stock <= 0`.
- Card menampilkan `<img src="{{ $product->image_url }}">` bila produk punya foto, dengan fallback **emoji** per kategori (peta `$emojiKategori`, mis. `kaos`/`baju` = kaus, `celana` = celana, `tas` = tas, default kaus) saat foto kosong.
- Modal pembelian dipertahankan dan diporting: klik "Beli Sekarang" mengisi nama/harga/kuantitas (dibatasi `max = stock`), method `updateTotal()` menghitung total, dan form submit ke `BUY_URL + buyId`. URL dasar diambil sekali via `const BUY_URL = @json(route('toko.buy', ['product_id' => 0]))` supaya tidak perlu regex tebakan di sisi klien. Tombol nonaktif untuk produk stok 0.
- **Chatbot tetap utuh**: seluruh CSS `.chatbot-*` dan JS (`toggleChatbot`, `sendMessage`, quick actions, typing indicator, `escapeHtml`) dipertahankan. Yang diperbaiki: ditambahkan `<meta name="csrf-token">` yang sebelumnya hilang, sehingga `fetch` POST ke `route('chatbot.message')` tidak lagi gagal 419.

### C. Controller Pelanggan

- `app/Http/Controllers/TokoController.php`: `index()` diubah dari `Product::all()` menjadi `Product::with('category')->latest()->get()` (eager loading untuk subtitle card + filter kategori), dan ditambahkan import `App\Models\Category` + variabel `$categories = Category::orderBy('name')->pluck('name')` yang dipakai untuk checkbox filter di sidebar.

### Verifikasi (tanpa menjalankan test)
Sesuai instruksi tidak ada `php artisan test`/`pint` yang dijalankan. Verifikasi dilakukan lewat request langsung ke HTTP kernel dengan skrip sementara (`_probe.php`, sudah dihapus):
- `GET /toko` -> 200; 4 card ter-render dengan `data-harga`/`data-nama`/`data-kategori` benar; 4 checkbox kategori dari DB muncul; seluruh id JS (`#grid`, `#rMin`, `#rMax`, `#apply`, `#reset`, `#noMatch`, `#buyModal`) ada; 0 sisa `{{` dan 0 sisa direktif Blade di output.
- `GET /staff/dashboard` -> 200; `enctype="multipart/form-data"`, `name="image"`, dan `accept="image/jpeg` ada; `name="stock"` tetap tidak ada.
- POST `staff/products` dengan PNG 1x1 -> 302 ke `?produk=8#pengajuan-restock`; baris tersimpan dengan `image = images/produk/<nama>.png`, `stock = 0`, kategori baru otomatis dibuat via `firstOrCreate`, file benar-benar ada di disk, dan `image_url` menghasilkan `http://localhost/images/produk/<nama>.png`. Data uji lalu dihapus (produk dulu, baru kategori, untuk menghormati FK `products_category_id_foreign`).
- Diff terhadap `git HEAD` dipakai untuk memastikan tidak ada perubahan tak sengaja.

### Temuan yang diperbaiki saat pengerjaan
- **Bug: foto terunggah tapi tidak tercatat.** Blok upload sudah mengisi `$data['image']`, namun `Product::create()` tidak pernah mengirim key itu ke database. Akibatnya file Perpindah ke `public/images/produk` tapi kolom `image` tetap `NULL` dan card tetap jatuh ke emoji. Diperbaiki dengan menambahkan `'image' => $data['image'] ?? null` pada `Product::create()`. Verifikasi ulang:end-to-end POST + cleanup.
- **Class kosong pada badge stok.** `class="mini {{ $product->stock > 0 ? '' : 'out' }}"` menghasilkan `class="mini "` (spasi sia-sia) saat stok tersedia; diubah jadi `class="mini{{ ... }}"` agar string kosong tidak pernah masuk ke atribut.
- **URL pembelian rapuh.** Versi pertama memakai `route('toko.buy', ['product_id' => 1])` lalu `.replace(/\/\d+$/, '/' + buyId)` di klien. Diganti `const BUY_URL` berisi id 0 supaya manipulasi string hilangtotal.

### Catatan
- Folder `public/images/produk` **dibuat saat runtime** oleh controller, bukan di-commit sebagai direktori kosong (git tidak melacak direktori kosong). Pastikan folder writable oleh proses web server.
- Tidak ada validasi update/hapus produk di scope Phase 14, jadi belum ada pembersihan file foto saat produk dihapus. Kalau nanti ada fitur hapus produk, file di `public/images/produk` harus ikut dihapus agar tidak jadi file sisa.

## 2026-09-30 - Phase 15: Revisi UI Katalog Toko (Tema Biru, Sidebar Shopee, Profil Topbar)

Refactor tampilan `resources/views/toko/index.blade.php` saja. Tidak ada perubahan controller, route, migration, atau view lain. Tidak menjalankan `php artisan test`/`pint`.

### 1. Palette warna -> Biru Modern
Aksen katalog diubah dari Oranye ke Biru agar seragam dengan tema utama aplikasi (Dashboard Staff/Manager yang memakai Bootstrap `primary`).

- `:root`: `--accent` `#ff9500` -> `#2563eb`; `--accent-soft` `#fff1dc` -> `#e8f0fe`.
- `.slider .track`: `background:#ffe3bd` (oranye) -> `#c7dcfd` (biru). Ini satu-satunya warna aksen yang masih di-hardcode di luar `:root`.
- Script dark mode: palet terang gained `--accent-soft:#e8f0fe`; palet gelap gained `--accent-soft:#1e3a5f` (semula `#3a2a10` cokelat yang tidak cocok dengan tema biru).
- Tidak perlu menyentuh komponen satu per satu karena seluruhnya sudah berbasis `var(--accent)`: `.logo`, `.nav a.active` + `::before`, `.avatar`, checkbox filter yang tercentang, `.slider .fill`, thumb slider, `.bubble`, `.btn`, `.buy`, dan focus ring (`:focus-visible`).
- Efek sampingbaiik: widget chatbot sudah `#0d6efd`, jadi sekarang satu warna tema dengan katalog.
- `.ic.alert{background:#ffe9e0}` (merah muda) sengaja dibiarkan karena itu warna notifikasi, bukan aksen.

### 2. Sidebar direstrukturisasi ala Shopee
Menu_params flatten tanpa sub-menu dan tanpa menu alamat terpisah.

- Link `Katalog Produk` yang sebelumnya punya anak menu (`Grid` / `Semua Produk`) dihapus seluruhnya; blok `.sub` beserta CSS `.sub` dan `.nav .chev` ikut dibuang.
- Link `Log Out` di dalam navigasi dihapus.
- Urutan menu final: `?? Overview` - `?? Katalog Produk` (`.active`, menunjuk `route('toko.index')`) - `?? Keranjang Belanja` - `?? Pesanan Saya` - `?? Produk Favorit`.
- `? Bantuan` tetap di `.nav.bottom` (dengan `.spacer` + `border-top`) sesuai permintaan.

### 3. Penyederhanaan search bar
- Input `#globalSearch` ("Cari produk...") di topbar dihapus. `.tools` diberi `margin-left:auto` supaya blok notifikasi/tema/profil tetap terdorong ke pojok kanan tanpa elemen tengah.
- Input `#search` di `.head` katalog dipertahankan sebagai satu-satunya sumber pencarian.
- CSS `.gsearch` (termasuk rule `.gsearch{display:none}` di media query 900px) dihapus supaya tidak ada CSS mati.
- JS: helper `onSearch` dua arah dihapus, diganti listener tunggal pada `#search` yang langsung menulis ke `state.q`. Baris reset `$('#globalSearch').value` ikut dibuang.

### 4. Pembersihan sidebar logout
- `<form action="{{ route('logout') }}">` + tombol "Keluar dari Akun" di bawah `.nav.bottom` dihapus.
- CSS `.logout` (yang hardcode `color:var(--accent)!important`) ikut dibuang.
- Konsekuensi yang Dicek: di `@media(max-width:900px)` sidebar `display:none`, namun `.topbar` tetap tampil, jadi tombol profil di pojok kanan atas tetap menjadi satu-satunya jalan keluar akun di mobile. Tidak ada kondisi tanpa jalan logout.

### 5 & 6. Profil akun di topbar -> Modal
Komponen profil dibuat interaktif, mengikuti pola `modal-b`/`modal-box` yang sudah dipakai halaman ini (bukan Bootstrap, karena view ini vanilla CSS).

- `.user` diubah dari `<div>` jadi `<button type="button" id="profileBtn">` supaya benar-benar bisa diklik, otomatis focusable, dan bisa dioperasikan via keyboard. Ditambah `aria-haspopup="dialog"` + `aria-expanded` (di-toggle `true`/`false` oleh JS) dan hover state.
- Teks role di bawah nama tidak lagi hardcode "Pelanggan", memakai `{{ ucfirst(Auth::user()->role ?? 'pelanggan') }}`.
- Modal baru `#profileModal` berisi: avatar inisial, nama lengkap, email (`word-break:break-all` supaya email panjang tidak merusak layout), badge role, dan tombol "Keluar dari Akun".
- Form logout dipindahkan ke dalam modal: `<form action="{{ route('logout') }}" method="POST">` + `@csrf` + tombol `btn danger` full-width. Ini satu-satunya titik logout di halaman.
- CSS baru: `.modal-head` + `.x` (tombol bulat tutup), `.profile-top`, `.role-badge` (pill biru memakai `--accent-soft`/`--accent`), `.btn.danger` (merah, sengaja beda dari aksen agar logout terbaca sebagai aksi destruktif).
- JS `openProfile()` / `closeProfile()`; ditutup lewat tombol âœ•, klik backdrop, atau Escape. Handler `keydown` yang sebelumnya hanya menutup `#buyModal` sekarang menutup kedua modal sekaligus.
- `z-index` modal (1055) vs chatbot FAB (1060) tidak konflik karena chatbot berada di pojok kanan bawah dan modal terpusat.

### 6b. Copy topbar
Judul "Product Grid" -> "Katalog Produk" dan subjudul "Lihat dan kelola seluruh produk yang terdaftar." -> "Temukan pakaian sesuai kebutuhan dan kebutuhanmu." (copy lama terasa seperti?? admin, bukan katalog pembeli; kata "kelola" tidak sesuai karena pelanggan tidak mengelola).

### Verifikasi (tanpa command test)
Verifikasi lewat request langsung ke HTTP kernel memakai skrip sementara (`_probe.php`, sudah dihapus). `GET /toko` -> 200, panjang 40.236 byte.
- Palet: keempat nilai biru baru ada; `ff9500`, `ffe3bd`, `fff1dc`, `3a2a10` nol kemunculan.
- Sidebar: kelima menu emoji + Bantuan ada; `class="active"` menempel pada "Katalog Produk".
- Search: `#search` ada; `globalSearch` dan `.gsearch` nol kemunculan.
- Logout: teks "Log Out" nol; "Keluar dari Akun" tepat 1; form POST mengarah ke `http://localhost/logout`.
- Profil: `profileBtn`/`profileModal`/`profileClose`/`role-badge`/`openProfile`/`closeProfile` ada; `.user` benar-benar `<button>`; isi modal ter-render dari data user nyata (nama, email, role "Pelanggan").
- Regresi: CSRF meta, chatbot, modal+form pembelian, filter+grid, dan 4 card produk tetap utuh; 0 sisa `{{` dan 0 sisa direktif Blade.

## 2026-09-30 - Phase 16: Font, Kontras Dark Mode, Ikon SVG, dan Link "Pesanan Saya"

Empat perbaikan UI pada katalog pelanggan. Semua tanpa menjalankan `php artisan test`/`pint`.

### 1. Font dan ukuran teks
- Font diganti dari `Inter` saja menjadi **Plus Jakarta Sans** dengan fallback `Inter, system-ui`: `https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:...`. Plus Jakarta Sans dipilih sebagai primary karena feel-nya lebih tegas dan cocok dengan tampilan dashboard yang sekarang, sementara Inter tetap dimuat sebagai fallback.
- Base font `body` naik dari `13px` ke **15px** dengan `line-height:1.5` dan `-webkit-font-smoothing:antialiased`.
- Hampir semua ukuran turunan diperbesar, termasuk yang tadinya ekstrem kecil:
  | Elemen | Sebelum | Sesudah |
  | --- | --- | --- |
  | `.title p` | 10px | 13px |
  | `.crumb` | 9px | 13px |
  | `.crumb .count` | inherit 9px | 13.5px |
  | `.card h3` | 12px | 15px |
  | `.card p` | 9px | 12.5px (`min-height` 26px -> 36px) |
  | `.foot b` | 11px | 15px + `font-weight:700` |
  | `.buy` | 11px | 14px + `font-weight:700` |
  | `.mini` (badge stok) | 10px | 12px, `font-weight:600` |
  | `.bubble` slider | 10px | 12.5px |
  | `.minmax input` | 10px | 12.5px |
  | `.modal-box h2` | 16px | 19px |
  | `.modal-box .rowline` | 12px | 14.5px |
  | `.modal-box input/select` | 12px | 14.5px |
  | `.modal-box label` | 10px | 13px |
  | `.user b` | 11px | 14px |
  | `.user small` | 9px | 12px |
  | `.role-badge` | 10px | 12.5px |
  | `.profile-top h3` | 15px | 18px |
  | `.profile-top p` | 11px | 13.5px |
  | `.nav a` | inherit 13px | 14.5px |
  | `.flash` | 12px | 14.5px |
  | `.acc` / `.range-title` | inherit | 14.5px |
  | `.checks label` | inherit | 14px |
  | `.pill` | 11px | 13.5px |
- Sizing ikut menyesuaikan supaya tidak cuma teks yang membesar: checkbox 15px -> 18px (centang 10px -> 12px + bold), thumb slider 12px -> 15px dengan border 2.5px, tinggi `.mini` 22px -> 24px, tinggi `.avatar` 32px -> 36px, `.ic` 34px -> 36px, `.x` 30px -> 34px, radius `.nav a` 10px -> 11px, kolom filter 174px/186px -> 196px/210px, `minmax` card 190px -> 215px, dan search 220px -> 250px. `gap` dan `padding` container juga dinaikkan 1-2px supaya proporsinya tetap seimbang.
- Tidak ada lagi teks di bawah 12px. Tiga nilai 12px yang tersisa adalah `.user small` (label role), glyph centang di dalam checkbox, dan `.mini` (badge "N Pcs") - ketiganya memang metadata kecil, bukan paragraf.

### 2. Dark mode high contrast
Palet gelap sebelumnya membuat teks pudar: `--muted:#9a9a9a` di atas `--card:#1f1f1f`, border `#2e2e2e` yang nyaris tak terlihat, dan aksen gelap `#3a2a10`.

- Latar dan kartu disolidkan ke palet slate: `--bg:#121827`, `--card:#1f2937` (sesuai acuan yang diminta).
- Teks utama `--ink:#f3f4f6` (putih-ish terang), teks sekunder `--muted:#9ca3af` (abu terang, bukan abu kusam).
- Border `--line:#374151` - jelas terlihat memisahkan kartu dari latar. Input/field `--field:#263244` dan tile produk `--tile:#2b3648` dibuat satu tingkat lebih terang dari kartu supaya tidak menyatu.
- Aksen dinaikkan ke `--accent:#3b82f6` karena `#2563eb` terlalu redup di atas latar biru gelap. `--accent-soft:#1e3a8a` (biru navy pekat) untuk latar tombol sekunder/badge role.
- **Token baru** untuk solving kontras warna: `--on-accent` (putih di terang, `#ffffff` di gelap) dipakai oleh `.logo`, `.avatar`, `.btn`, `.bubble`, dan checkbox centang, sehingga warna teks di atas aksen tidak ikut pudar saat palet bertukar. `--alert` dan `--danger-soft`/`--danger` juga jadi token karena nilai merahnya perlu versi gelap (`#7f1d1d` / `#fca5a5`).
- `.slider .track` `#c7dcfd` (biru sangat terang) ikut menyamar di latar gelap; thumb sekarang memakai `border-color:var(--card)` supaya tidak kehilangan bentuk.
- Overlay modal dinaikkan dari `rgba(0,0,0,.45)` ke `rgba(2,6,23,.62)` supaya modal benar-benar terpisah dari isi di belakang saat mode gelap.
- Logika toggle disederhanakan: palet `dark` dan `light` sekarang dua objek utuh, dan deteksi mode memakai `r.getPropertyValue('--bg') === dark['--bg']` (sebelumnya membandingkan dengan string yang tidak ada di palet mana pun, sehingga deteksi mode selalu salah).
- Semua nilai palet lama (`#141414`, `#1f1f1f`, `#2e2e2e`, `#2a2a2a`, `#2b2c33`, `#1e3a5f`) sudah nol kemunculan.

### 3. Emoji -> ikon SVG vector
Seluruh emoji di sidebar navigation diganti SVG inline bergaya Lucide (stroke outline, `stroke="currentColor"`, `stroke-width:1.9`, `fill:none`) supaya warna ikon otomatis mengikuti state `active`/`hover` dan ukurannya konsisten dengan font, berbeda dari emoji yang warnanya hardcode dan bentuknya beda antar platform.

| Menu | Ikon |
| --- | --- |
| Overview | LayoutDashboard (4 panel) |
| Katalog Produk | ShoppingBag |
| Keranjang Belanja | ShoppingCart |
| Pesanan Saya | Package |
| Produk Favorit | Heart |
| Bantuan | HelpCircle (lingkaran + ?) |

- `<nav class="nav">` diberi `aria-label="Navigasi utama"`, tiap `<svg>` diberi `aria-hidden="true"` supaya screen reader membacakan label teks, bukan menebak ikon.
- Teks menu dibungkus `<span>` supaya style `gap`/`align-items` sidebar rapi dan tidak memunculkan underline pada `<a>`.
- CSS: `.nav a{font-size:14.5px;font-weight:500}` + `.nav a svg{width:19px;height:19px;flex-shrink:0}`.
- Kontras warna link kaku `color:#444` dan label filter `color:#555` (keduanya gelap, tidak terbaca di dark mode) diganti ke `var(--ink)`. `color:#ccc` pada border checkbox diganti `var(--line)`. `.ic{background:#fff}` diganti `var(--card)` karena lingkaran putih akan menyilaukan di mode gelap.
- Emoji yang tersisa di luar sidebar memang disengaja: fallback kategori pada thumbnail produk (bukan navigasi) dan chip quick-action chatbot. Sidebar nav sendiri 0 emoji.

### 4. Integrasi link navigasi
Ditemukan dua fakta dari skema database sebelum memutuskan arah link:
- `transactions` **tidak** punya kolom produk; relasi produk ada di tabel terpisah `transaction_details`, dan `TokoController::buyProduct()` justru tidak pernah mengisi tabel tersebut. Jadi daftar pesanan hanya bisa menampilkan data level transaksi (nomor, tanggal, total, metode bayar, status) - riwayat item produk per pesanan tidak tersedia tanpa mengisi `transaction_details`.
- **Tidak ada** tabel cart maupun favorit di skema, jadi menu Keranjang dan Produk Favorit tidak bisa diarahkan ke route nyata.

Keputusan:
- **Pesanan Saya** -> route baru `toko.pesanan` (`GET /toko/pesanan`), dipakai lewat `{{ route('toko.pesanan') }}`.
  - `routes/web.php`: route ditambahkan di dalam middleware `auth`, sebelum `toko.buy`.
  - `TokoController::pesanan()`: `Transaction::where('customer_id', auth()->id())->latest('date')->get()` - data difilter ke user yang sedang login, bukan semua transaksi. `auth()->id()` dipakai (setara `auth()->user()->id` yang diminta) karena lebih ringkas.
  - `resources/views/toko/pesanan.blade.php` (baru): halaman standalone dengan font dan palet yang sama seperti katalog. Isinya 4 kartu ringkasan (Total Pesanan, Selesai, Sedang Diproses, Total Belanja) lalu daftar pesanan bernomor `#00001`, badge status berwarna, tanggal, metode bayar, dan total. `@empty` memberi empty state dengan CTA "Mulai Belanja". Badge memetakan `pending/processing/completed/cancelled` ke label Indonesia (Menunggu Pembayaran/Diproses/Selesai/Dibatalkan) lewat array `$labelStatus` dengan fallback `ucfirst`.
- **Overview** dan **Katalog Produk** -> keduanya `{{ route('toko.index') }}` (halaman katalog memang beranda pelanggan).
- **Keranjang / Favorit / Bantuan** -> `href="#"` + atribut `data-info` yang membuka modal info baru `#infoModal` berisi judul, ikon, dan penjelasan jujur bahwa fiturnya masih dalam pengembangan (bukan link mati yang diam-diam tidak melakukan apa-apa). Modal ditutup lewat tombol âœ•, tombol "Mengerti", klik backdrop, atau Escape (Escape sekarang menutup buy modal, info modal, dan modal profil sekaligus). Definisi teks & ikon disimpan di object `infoContent` di script supaya tidak ada duplikasi HTML.
- CSS baru: `.info-ico` (kotak ikon biru 56px) dan `.info-text`, plus `.modal-box{color:var(--ink)}` agar teks modal ikut terang di mode gelap.

### Verifikasi (tanpa command test)
Skrip sementara (`_probe.php`, `_probe2.php`, sudah dihapus), request langsung ke HTTP kernel.
- `GET /toko` -> 200 (47.393 byte), `GET /toko/pesanan` -> 200 (9.623 byte).
- Font: link Google Fonts memuat Plus Jakarta Sans; `font-size:15px` ada; hanya 3 sisa nilai 12px (`.user small`, glyph checkbox, badge `.mini`) dan 0 di halaman pesanan.
- Dark mode: tenth token palet gelap ada di output; keenam nilai palet lama nol.
- Ikon: 6 SVG di sidebar nav, 0 emoji di sidebar nav, keenam label menu ada.
- Link: Overview & Katalog -> `/toko`; Pesanan Saya -> `/toko/pesanan`; Keranjang/Favorit/Bantuan -> `#` dengan modal.
- Pesanan Saya: ringkasan benar (user uji punya 6 transaksi, total belanja `Rp 980.000`, nomor `#00006`); cabang `@empty` dicek terpisah dengan koleksi kosong dan memuat "Belum ada pesanan" + "Mulai Belanja" + tiga angka nol.
- Regresi: CSRF meta, chatbot, modal+form pembelian, filter+grid, 4 card produk, modal profil, dan satu form logout utuh; 0 sisa `{{` dan 0 sisa direktif Blade di kedua halaman; aksen oranye nihil.

### Catatan
- `buyProduct()` tetap tidak menulis ke `transaction_details`, jadi halaman Pesanan Saya menampilkan transaksi tanpa rincian item. Mengisi tabel itu adalah pekerjaan terpisah (butuh keputusan apakah quantity diotong di `transaction_details` atau tidak) dan sengaja tidak dikerjakan di Phase 16.
- Halaman Pesanan Saya memakai palet terang saja, tidak punya tombol dark mode seperti katalog. Ini keputusan sadar agar tidak menambah token; bisa menyusul bila diperlukan.

## 2026-10-01 - Phase 17: KMS (Deskripsi & Foto Produk) + Backend Produk Favorit

### Temuan sebelum mengerjakan
Empat fakta yang mengubah keputusan teknis:
- `products.image` **sudah ada** dari migration `2026_09_30_120000_add_image_to_products_table.php` (Phase 14) dan sudah pernah dijalankan. Jadi Phase 17 **tidak** membuat migration image lagi, hanya menambah `description`.
- **Tidak ada middleware role sama sekali** (`bootstrap/app.php` -> `withMiddleware()` kosong). Route produk hanya dijaga `auth`, jadi siapa pun yang login bisa menyentuh route admin. Pengecekan role dibuat langsung di controller lewat `abort_unless(Auth::user()->role === ...)`, tanpa menambah middleware baru.
- `transaction_details.product_id` (migration `2026_08_26_085133`, baris 14) memakai foreign key **tanpa** `cascade`, sedangkan `restock_requests.product_id` sudah `cascadeOnDelete`. Artinya produk yang pernah terjual akan **ditolak** FK kalau dihapus paksa.
- `tests/Feature/StaffDashboardTabTest.php` mengunci HTML dashboard staff dengan assertion negatif yang ketat, di antaranya `assertStringNotContainsString('bootstrap.Modal.getOrCreateInstance', $html)` (baris 101) dan `assertStringNotContainsString('name="stock"', $html)` (baris 103).

### 1. Database
- Migration `2026_10_01_000001_add_description_to_products_table.php`: `$table->text('description')->nullable()->after('image')`. Dipakai `text` (bukan `string`) supaya deskripsi panjang tidak terpotong oleh batas 255 karakter. Nullable supaya 4 produk lama tetap utuh.
- Migration `2026_10_01_000002_create_favorites_table.php`: tabel `favorites` dengan `user_id` -> `users` dan `product_id` -> `products`, keduanya `cascadeOnDelete`, plus `timestamps`.
  - **Unique compound `['user_id', 'product_id']`** (`favorites_user_id_product_id_unique`) supaya satu user tidak bisa memfavoritkan produk yang sama dua kali, sekaligus melayani query "produk yang difavoritkan user X".
  - Konsekuensi: logika toggle **wajib** `first` + `create`/`delete`, **bukan** `updateOrCreate` yang bisa meledak jadi duplicate saat update.
- Dijalankan `php artisan migrate --force` (tidak pernah `migrate:fresh`). Verifikasi `php artisan db:table`: `products` jadi 12 kolom dengan `description` (text) tepat setelah `image` (varchar 255); `favorites` 5 kolom, 2 FK cascade, 1 unique compound.

### 2. Model
- `app/Models/Favorite.php` (baru): `$fillable = ['user_id', 'product_id']`, relasi `user()` dan `product()`.
- `app/Models/Product.php`: `description` masuk `$fillable`, relasi `favorites()` (hasMany).
- `app/Models/User.php`: relasi `favorites()` (hasMany).

### 3. Fitur Kelola Foto Produk (upload / ganti / hapus)
Trait `app/Support/HandlesProductImage.php` dipakai bersama oleh `StaffController` dan `ManagerController` supaya logika file tidak diduplikasi:
- `storeProductImage(?UploadedFile)`: bikin folder bila belum ada, nama file `time()_random8.ext` (mencegah tabrakan nama), return path relatif `images/produk/<nama>`.
- `syncProductImage($file, $currentPath, $removeCurrent)`: satu pintu untuk tiga kemungkinan.
- `deleteProductImage($path)`: `unlink` diabaikan bila file sudah tidak ada.

Tiga kemungkinan itu diameterskan lewat checkbox **"Hapus foto"** (`name="remove_image"`, aturan validasi `nullable|boolean`) di overlay edit staff & admin, dengan perilaku berikut:

| Situasi di form | Yang terjadi pada foto | Kolom `products.image` |
| --- | --- | --- |
| Tidak ada file baru, checkbox tidak dicentang | Foto lama **dipertahankan** | tetap sama |
| Ada file baru dipilih | File baru tersimpan, **file lama dihapus** dari disk | diganti ke file baru |
| Checkbox "Hapus foto" dicentang | File lama **dihapus** dari disk, input file otomatis dinonaktifkan | jadi `NULL` |
| Produk dihapus (`destroyProduct`) | File foto di-unlink sebagai bagian dari penghapusan | baris produk ikut terhapus |

- Input file otomatis `disabled` ketika checkbox "Hapus foto" dicentang, supaya satu submit tidak pernah mengirim dua aksi yang bertabrakan (hapus + unggah bersamaan).
- Prinsipnya: **file lama tidak pernah dihapus sebelum file baru benar-benar tersimpan**, supaya produk tidak berakhir tanpa gambar hanya karena gagal simpan.
- Folder tetap `public/images/produk/` (bukan `storage/app/public`), jadi **tetap tidak perlu `php artisan storage:link`** dan `asset()` langsung bekerja.
- Sudah diuji alurnya sampai tuntas lewat HTTP kernel: unggah pertama -> foto masuk `images/produk/` dan file ada di disk; update tanpa file baru -> foto lama tetap ada; ganti foto -> path berubah, file baru ada, file lama hilang dari disk; centang "Hapus foto" -> kolom `NULL` dan file terhapus; produk dihapus -> file ikut terhapus.

### 4. Update & delete produk
- `StaffController::storeProduct()`: validasi tambah `description` (`nullable|string|max:2000`) dan `description` ikut masuk `Product::create()`. Logika upload-inline dipecah ke trait.
- `StaffController::updateProduct()` (baru): `abort_unless(role in ['staff','admin'], 403)`. Staff boleh mengubah kategori (sama seperti saat membuat produk), nama, type, size, color, harga, deskripsi, dan foto. **`stock` tidak bisa diubah** dari form ini - aturan Phase 11 (stok hanya lewat pengajuan restock) tetap dijaga. Field `_produk_id` dikirim(hidden) hanya untuk membantu JS membuka kembali overlay yang benar saat validasi ditolak.
- `ManagerController::index()`: kini juga mengirim `$products` + `$categories` karena Manager boleh mengelola master produk.
- `ManagerController::updateProduct()` (baru): aturan sama dengan staff.
- `ManagerController::destroyProduct()` (baru): `abort_unless(role === 'admin', 403)`. **Produk yang sudah pernah terjual ditolak hapus** dengan pesan jelas, karena `transaction_details` memakai FK tanpa cascade dan riwayat penjualan tidak boleh ikut terhapus diam-diam. Kalau aman, `restock_requests` dan `favorites` terhapus otomatis oleh cascade, file foto di-unlink dari disk, baru produk dihapus.
- ~~`ManagerController::storeProduct()`~~: **sudah dihapus** pada revisi Phase 17 (lihat bagian "Revisi" di bawah) karena Manager difokuskan ke RUD + Approval saja. Penambahan master produk hanya lewat `StaffController::storeProduct()`.
- Route produk (semua di dalam middleware `auth`, dicek role di controller):
  - `PUT /staff/products/{product}` -> `staff.products.update`
  - `PUT /admin/products/{product}` -> `admin.products.update`
  - `DELETE /admin/products/{product}` -> `admin.products.destroy`
  - ~~`POST /admin/products` -> `admin.products.store`~~: **dihapus** pada revisi Phase 17.

### 5. Halaman pelanggan: detail & favorit
- `GET /toko/produk/{product}` -> `toko.show` - `TokoController::show()` memuat `with('category')` + mengecek status favorit lewat helper `apakahFavorit()`.
- `POST /toko/favorit/{product}` -> `toko.favorit.toggle` - `toggleFavorite()` memakai pola `first` + `create`/`delete` (bukan `updateOrCreate`, demi menghormati unique index). Balas JSON `{favorit, jumlah, pesan}` bila request `expectsJson()` (dipakai `fetch()`), dengan fallback `back()->with('success')` untuk submit tanpa JavaScript.
- `GET /toko/favorit` -> `toko.favorit` - daftar produk yang ditandai pengguna, **diurutkan dari yang paling baru difavoritkan**. Query memakai `join('favorites')` + `orderByDesc('favorites.created_at')` + `select('products.*')`, bukan `whereHas` (lihat catatan bug di bawah).
- `resources/views/layouts/toko-shell.blade.php` (baru): layout bersama berisi sidebar, topbar, dark mode, modal profil, modal info, dan widget chatbot. Dipakai `toko/show.blade.php` dan `toko/favorit.blade.php` supaya tidak menduplikasi ~200 baris CSS di dua file. Katalog (`toko/index.blade.php`) **tidak** disentuh strukturnya karena sudah bekerja dan diuji Phase 16.
- `resources/views/toko/show.blade.php` (baru): foto besar, tag kategori/type/warna/ukuran, harga, status stok, deskripsi lengkap (dengan fallback jujur bila kosong), spesifikasi, tombol favorit besar, tombol beli (modal pembelian yang sama seperti katalog), dan link ke halaman favorit.
- `resources/views/toko/favorit.blade.php` (baru): grid produk favorit dengan tombol hati untuk melepas, plus empty state + CTA "Jelajahi Katalog".
- `resources/views/toko/index.blade.php`: tombol hati SVG di pojok kanan atas tiap thumbnail, judul kartu jadi link ke `toko.show`, sidebar "Produk Favorit" diarahkan ke `route('toko.favorit')`, entri `infoContent.favorit` dihapus, dan toggle `fetch()` ditambahkan (tombol dikunci selama request supaya klik ganda tidak menyebabkan dua toggle beruntun).

### 6. Form edit tanpa Modal Bootstrap
- Ekor `tests/Feature/StaffDashboardTabTest.php` melarang `bootstrap.Modal.getOrCreateInstance` (baris 101) dan `name="stock"` (baris 103), serta menghitung presisi `substr_count($html, '<tr data-product-row ') === 2` (baris 113) dan 5 `dashboard-section` (baris 139/141).
- Karena itu form edit memakai **overlay kustom** (`.produk-overlay`, `position:fixed` + atribut `hidden`) yang **diletakkan DI LUAR** wrapper `#staffDashboard` dan di luar semua `.dashboard-section`. Alasan teknisnya: section dashboard berpindah tab memakai kelas `d-none` (`display:none !important`), sehingga modal di dalamnya akan ikut tersembunyi walau sudah berstatus `.show`.
- Staff: kolom "Aksi" baru di tabel Monitoring Stok dengan tombol Edit; data form diambil dari atribut `data-*` pada tombol tersebut, jadi tidak ada request tambahan. `colspan` tabel dinaikkan 9 -> 10. Baris produk tetap memakai `data-product-row` (assertion baris 113 aman) dan `data-search` tidak diubah formatnya (assertion baris 115 aman).
- Overlay diisi JS, ditutup lewat tombol, backdrop, atau Escape. Checkbox "Hapus foto" langsung menonaktifkan input file supaya dua aksi tidak bertabrakan dalam satu submit. Bila validasi ditolak, overlay **dibuka kembali** dengan nilai `old()` sehingga staff tidak perlu mengetik ulang.
- `layouts/app.blade.php` (dipakai staff & admin): CSS `.produk-overlay`, `.produk-overlay-box/-head/-body/-foot`, `.produk-preview` ditambahkan lewat `@stack('styles')`.
- Admin: section baru `#kelola-produk` berisi form tambah produk (dengan deskripsi) + tabel daftar produk lengkap (foto, kategori, tipe, ukuran, warna, harga, stok) dengan tombol Edit dan Hapus. Hapus memakai `onsubmit="return confirm(...)"` sesuai permintaan. Sidebar admin dapat menu "Kelola Produk". Anchor `#persetujuan-restock` dan `#riwayat-stok` yang diuji suite tetap dipertahankan.

### 7. Bug yang ditemukan saat verifikasi render (sudah diperbaiki)
Temuan awal "sintaks PHP OK" ternyata belum cukup: dua bug baru muncul saat request benar-benar dirender, karena `php -l` dan kompilasi Blade tidak menyentuh database maupun resolution variabel.
- **`/toko/favorit` balas HTTP 500.** Penyebab: `whereHas('favorites')` menghasilkan subquery `EXISTS`, sehingga tabel `favorites` **tidak** di-join ke query luar. MySQL menolak `ORDER BY favorites.created_at` dengan `SQLSTATE[42S22] Unknown column 'favorites.created_at' in 'order clause'`. Diperbaiki dengan `join('favorites', 'favorites.product_id', '=', 'products.id')` + `where('favorites.user_id', ...)` + `orderByDesc('favorites.created_at')` + `select('products.*')` supaya tidak ada benturan kolom `id`/`created_at`.
- **`POST /toko/favorit/{product}` balas HTTP 500.** Penyebab: `toggleFavorite(Product $product)` memanggil `$request->expectsJson()` padahal `$request` tidak pernah diinjeksi -> `ErrorException: Undefined variable $request` di `TokoController.php:76`. Perilaku DB-nya sebenarnya benar (sudah create/delete), hanya serialization respons yang meledak. Diperbaiki jadi `toggleFavorite(Request $request, Product $product)`; `Illuminate\Http\Request` sudah ada di `use` statement.
- **`App\Models\TransactionDetail` tidak pernah ada.** `ManagerController` meng-`use` class itu untuk mengecek riwayat penjualan, tapi folder `app/Models` hanya berisi 8 model tanpa `TransactionDetail`. Gate "tolak hapus bila terjual" akan meledak `Class not found` saat dipanggil. Diperbaiki dengan membuat model `TransactionDetail` (`transaction_id`, `product_id`, `quantity`, `subtotal` + cast, relasi `transaction()`/`product()`) dan menambah relasi `Transaction::details()`.
- **`StaffController::storeProduct()` tidak punya cek role**, padahal `updateProduct()` sudah punya `abort_unless(role in ['staff','admin'])`. Artinya pelanggan yang sudah login bisa menambah produk lewat `POST /staff/products`. Diperbaiki dengan `abort_unless` yang sama.
- **Overlay admin tidak reopen saat validasi gagal.** Staff sudah punya pola `@if($errors->any() && old('_produk_id'))`; admin belum punya field `_produk_id` sama sekali. Diperbaiki: ditambah `<input type="hidden" name="_produk_id" id="editProdukAdminId">`, diisi JS dari `btn.dataset.editProduk`, lalu reopen overlay + tampilkan pesan error.
- **`Str` tanpa import ternyata bukan bug.** Dugaan awal "`\Str` tidak ditemukan" ternyata salah: Laravel mendaftarkan alias global `Str` => `Illuminate\Support\Str` di core, sudah diverifikasi `class_exists('Str')` dan avatar sidebar ter-render `"P"`. Tidak ada perubahan kode untuk hal ini.

### Verifikasi (tanpa command test)
- `php -l` pada 10 file PHP yang diubah: semua `No syntax errors detected`.
- `php artisan route:list`: 7 route baru terdaftar (`toko.show`, `toko.favorit`, `toko.favorit.toggle`, `staff.products.update`, `admin.products.store`, `admin.products.update`, `admin.products.destroy`).
- `php artisan db:table products|favorites|transaction_details`: skema sesuai rencana.
- Kompilasi Blade (`blade.compiler->compileString`) pada 8 template: semua berhasil.
- **Render nyata lewat HTTP kernel** (bukan `php artisan test`): `/toko` 200, `/toko/produk/{id}` 200, `/toko/favorit` 200 (setelah bug di atas diperbaiki), `/staff/dashboard` 200, `/admin/dashboard` 200.
- Assertion `StaffDashboardTabTest` dicek satu per satu terhadap HTML hasil render: 11 assertion negatif lolos (tidak ada `bootstrap.Modal`, `name="stock"`, `modalTambahProduk`, `data-reopen`, `Stok Awal`, `<th>Catatan</th>`, `data-bs-toggle="tab"`, `sidebar-footer`, dll) dan 18 assertion positif lolos, termasuk hitungan presisi `dashboard-section` = 5 dan `d-none` = 4.
- **Alur favorit diuji dua arah**: toggle #1 -> `favorit=true`, 1 baris DB; toggle #2 -> `favorit=false`, 0 baris DB. Unique index ditolak duplikat dengan benar (`Duplicate entry '1-3'`).
- **Otorisasi diuji dengan role nyata**: pelanggan mendapat **403** untuk `PUT staff.products.update`, `PUT admin.products.update`, `DELETE admin.products.destroy`, `POST admin.products.store`; staff mendapat **403** untuk `DELETE admin.products.destroy`; staff & admin lolos otorisasi (302) untuk route update; tamu **302** ke login untuk `/toko/favorit`.
- **Gate riwayat penjualan diuji dengan data transaksi sungguhan** (produk uji + `transactions` + `transaction_details` + `favorites`, lalu dibersihkan): `DELETE` ditolak, flash error tampil, produk **tetap ada**, detail & transaksi tetap utuh.
- **Alur foto diuji penuh** (upload -> update tanpa file -> ganti foto -> hapus foto -> validasi gagal): file baru masuk `public/images/produk/`, foto lama **dipertahankan** saat tidak ada file baru, file lama **dihapus dari disk** saat diganti/dicentang hapus, kolom jadi `null` setelah hapus, dan `stock` **tetap** karena tidak bisa diubah dari form.
- Semua data uji dibuat dan dibersihkan kembali; database kembali ke 4 produk / 4 kategori / 0 favorit / 0 `transaction_details`, dan folder `images/produk/` kosong.
- Tidak menjalankan `php artisan test`, `pint`, atau `view:cache` sesuai instruksi.

### Catatan & pekerjaan lanjutan
- `buyProduct()` **masih** tidak menulis ke `transaction_details`, jadi halaman detail produk tidak menampilkan riwayat "produk ini pernah dibeli" dan tabel `transaction_details` tetap kosong di data lama. Gate "tolak hapus bila terjual" sudah diuji dan terbukti bekerja, tapi di aplikasi nyata baru akan terpicu setelah `buyProduct()` mulai menulis detail transaksi.
- Overlay edit di staff & admin masih duplikasi markup (hanya ID berbeda) dan sekarang punya blok reopen-after-validate yang juga kembar. Belum di-DRY karena masih perlu diuji manual lewat browser; nanti bisa dijadikan satu partial `@include` dengan prefix ID.
- Halaman detail & favorit memakai `layouts/toko-shell.blade.php`, sedangkan katalog masih standalone. Kalau nanti katalog ikut memakai shell, CSS inline di `toko/index.blade.php` (~170 baris) bisa dihapus.
- Gambar produk masih disimpan langsung di `public/images/produk/` tanpa versioning, jadi file lama tidak pernah di-cache-bust. Tidak masalah untuk skala praktikum.
- Verifikasi baru dilakukan lewat HTTP kernel, jadi perilaku JavaScript (tombol hati `fetch()`, buka/tutup overlay, konfirmasi hapus, chatbot) **belum** diuji di browser sungguhan. Yang sudah pasti benar adalah markup, route, otorisasi, validasi, dan query database.

---

## 2026-10-01 - Revisi Phase 17: Fokus RUD + Approval di Manager, Tombol Preview Detail

> **Catatan:** sub-bagian 2 di bawah ditulis kembali pada revisi berikutnya (lihat "Revisi 2" di akhir dokumen) karena tombol "Lihat" tidak lagi membuka halaman publik, melainkan modal read-only di dalam dashboard.

Dua permintaan revisi setelah Phase 17 selesai diuji. Tidak ada perubahan migration dan tidak ada perubahan pada `StaffDashboardTabTest`.

### 1. Form "Tambah Master Produk" disembunyikan dari dashboard Manager
- Alasan: halaman Manager difokuskan pada **RUD (lihat, ubah, hapus) + Approval OAS**, bukan penambahan master produk. Penambahan produk tetap ada di dashboard Staff (`POST /staff/products`), jadi tidak ada celah fungsi yang hilang dari sistem.
- `resources/views/admin/dashboard.blade.php`: seluruh card "Tambah Master Produk Baru" dihapus, termasuk form-nya (`adminKategori`, `adminNama`, `adminHarga`, `adminType`, `adminSize`, `adminColor`, `adminImage`, `adminDeskripsi`) beserta `@error` masing-masing.
  - Card **"Daftar Master Produk"** tetap ada dan kini menjadi satu-satunya kartu di `#kelola-produk`.
  - Teks baris kosong diubah dari "Tambahkan produk pertama di atas." menjadi "Master produk ditambahkan dari dashboard Staff." karena tidak lagi ada form di atasnya.
  - Overlay edit produk **tidak** ikut terpengaruh, jadi kolom deskripsi, ganti foto, dan checkbox "Hapus foto" untuk produk yang sudah ada tetap berfungsi.
- `routes/web.php`: route `POST /admin/products` (`admin.products.store`) dihapus.
- `app/Http/Controllers/ManagerController.php`: method `storeProduct()` dihapus, digantikan komentar yang menjelaskan pembagian tanggung jawab. Konsekuensinya `admin.products.store` dan `admin.products.update` yang tadinya punya aturan berbeda (khusus `admin`) sekarang seragam di `updateProduct()`: `abort_unless(role in ['staff','admin'], 403)`.
- Efek samping yang disengaja: endpoint yang bisa menambah produk dari sisi Manager hilang, sehingga tidak ada lagi cara menambahkan produk tanpa melewati Staff.

### 2. Tombol "Lihat / Preview Detail" di tabel produk Staff & Admin
- ~~Versi pertama: menuju halaman detail produk yang sudah ada, `route('toko.show', $product->id)` -> `GET /toko/produk/{product}` dengan `target="_blank" rel="noopener"`.~~ **Digantikan** oleh modal read-only in-dashboard, lihat bagian "Revisi 2" di akhir dokumen.
- Route `toko.show` hanya dilindungi middleware `auth` (tanpa pembatasan role), jadi **sudah diuji dan bisa dibuka staff maupun admin** (HTTP 200, nama produk tampil). Tidak perlu route baru.
- `resources/views/staff/dashboard.blade.php`: tombol `btn-outline-info btn-sm` dengan ikon `bi-eye` + teks "Lihat" dan `class="btn-lihat-produk"`, ditaruh di sel `Aksi` yang sama **sebelum** tombol Edit.
- `resources/views/admin/dashboard.blade.php`: tombol `btn-outline-info btn-sm` dengan ikon `bi-eye` saja (kebalikan dari staff yang ikon + teks, karena kolom admin sudah padat Edit + Hapus) dan `class="btn-lihat-produk-admin"`, ditaruh sebelum tombol Edit.
- Kedua tombol memakai `target="_blank" rel="noopener"` supaya Manager/Staff membuka preview di tab baru dan **tidak kehilangan posisi scroll** di daftar produk. `rel="noopener"` dipakai karena `target="_blank"` tanpa itu membuka `window.opener` ke tab asli.
- **Jumlah kolom tidak berubah** (tombol masuk ke sel `Aksi` yang sudah ada), jadi `colspan="10"` pada baris kosong staff maupun admin tetap benar dan assertion `substr_count($html, '<tr data-product-row ')` pada `StaffDashboardTabTest` baris 113 tidak terganggu.

### 3. Catatan fitur "Hapus Foto"
- Fitur hapus foto dipromuskan jadi sub-bagian tersendiri di atas ("Fitur Kelola Foto Produk") dengan tabel empat perilaku, supaya tidak lagi hanya tersirat di dalam catatan trait.
- Ringkasnya: hapus foto bergantung pada checkbox `remove_image`, tombolnya ada di overlay edit **staff maupun admin**, file lama di-unlink dari disk, dan kolom `image` jadi `NULL`. Form produk baru **tidak** punya tombol hapus foto, karena belum ada file untuk dihapus.

### 4. Insiden: foto produk milik pengguna terhapus saat verifikasi (kronologi & dampak)
- **Apa yang terjadi**: saat memverifikasi, produk #3 "Kemeja Hitam Polos" terlihat punya `image` terisi. File itu diasumsikan dibuat oleh probe pengujian milik agen, lalu `image` dikembalikan ke `NULL` dan filenya dihapus.
- **Ternyata salah**: `UploadedFile::fake()->image()` menghasilkan gambar **10x10** piksel (lihat `vendor/laravel/framework/src/Illuminate/Http/Testing/FileFactory.php:34`, default `$width = 10, $height = 10`). File yang dihapus berukuran **1024x1024**, yaitu foto asli milik pengguna yang diunggah lewat browser, bukan artefak probe. Teks deskripsi panjang yang menyertaina juga merupakan input asli, bukan string tetap yang dipakai probe.
- **Kerugian**: file `public/images/produk/1790758786_ttYGgZ65.jpg` hilang permanen. Penghapusan dilakukan `unlink()` dari PHP, jadi tidak masuk Recycle Bin, dan folder `public/images/` tidak pernah dilacak git sehingga tidak ada salinan untuk dipulihkan. Kolom `products.image` sudah dikembalikan `NULL` supaya tidak ada referensi ke file yang hilang. **Deskripsi produk #3 tidak rusak.** Foto perlu diunggah ulang oleh pengguna.
- **Akar masalah**: asal-usul data ditebak dari pola nama file dan timestamp, bukan dari isi file. Kemungkinan seharusnya diperiksa lebih dulu (dimensi gambar) sebelum bertindak destruktif, atau langsung dikonfirmasi ke pengguna.
- **Pelajaran untuk sesi berikutnya**: jangan pernah menjalankan bersih-bersih massal pada folder aset atau baris database milik pengguna hanya berdasarkan dugaan bahwa datanya "data uji". Verifikasi dulu dengan sinyal yang tidak ambigu (dimensi file, isi kolom, atau bertanya ke pengguna), dan lebih utamakan membuat data uji dengan nama yang jelas dan/atau di database terpisah agar tidak pernah tertukar dengan data asli.

### 5. Hasil verifikasi revisi (tanpa `php artisan test`)
- `/staff/dashboard` dan `/admin/dashboard` tetap HTTP 200 setelah perubahan.
- Semua assertion `StaffDashboardTabTest` diuji ulang terhadap HTML hasil render dan tetap lolos: 11 assertion negatif (L98-104, L108, L136, L142-143), assertion positif L97, L105, L110, serta hitungan presisi L109 `logout` = 1, L113 `<tr data-product-row ` = 2 pada database uji, L139 `dashboard-section` = 5, L141 `d-none` = 4. Format `data-search` (L115) tidak berubah sama sekali.
- Assertion admin L226-235 juga tetap lolos, termasuk `assertStringNotContainsString('#riwayat-restock"', $html)`.
- Diverifikasi bahwa form tambah produk **tidak lagi** ada di HTML admin (`Tambah Master Produk Baru`, `id="adminNama"`, dan `action` ke `admin/products` POST semuanya hilang), sementara overlay edit admin masih ada lengkap dengan `name="description"`, tombol hapus `confirm()`, dan section `#kelola-produk`.
- Diverifikasi `admin.products.store` sudah tidak terdaftar di router dan `ManagerController::storeProduct()` sudah tidak ada.
- Diverifikasi link preview benar-benar mengarah ke `/toko/produk/{id}` untuk keempat produk di kedua halaman, dan halaman detailnya bisa dibuka dengan role staff maupun admin (HTTP 200). **Catatan: baris ini sudah usang** karena link-nya diganti modal pada "Revisi 2" di bawah.

---

## 2026-10-01 - Revisi 2: Tombol "Lihat" jadi Modal Read-Only In-Dashboard

Permintaan: tombol "Lihat" di tabel produk Staff dan Admin **tidak boleh** memindahkan pengguna ke halaman publik. Staff/Admin harus tetap berada di dashboard masing-masing, dan isi detail harus **read-only** (tanpa form edit, tanpa tombol simpan/hapus, hanya ada tombol Tutup).

### 1. Pendekatan
- Markup ditulis inline di masing-masing view, **meniru pola overlay Edit yang sudah jalan** (bukan partial bersama) supaya konsisten dengan kode sekarang dan tidak menyentuh alur edit yang sudah terverifikasi.
- "Read-only" dimaknai secara struktural: **overlay tidak memuat satu pun kontrol form** (`<form>`, `<input>`, `<textarea>`, `<select>`, `type="submit"`). Semua isian dirender sebagai teks di dalam `<dl class="row">`. Ini yang membuat `name="stock"` (forbidden di `StaffDashboardTabTest` L103) mustahil muncul, bukan sekadar tidak diKetik.
- Overlay memakai CSS `.produk-overlay` yang sudah ada, **bukan** Bootstrap Modal, sesuai framework yang dipakai aplikasi ini.
- Overlay ditempatkan **di luar** wrapper/tab yang memakai `d-none` (staff: sesudah overlay Edit, sebelum `@endsection`; admin: sesudah overlay Edit), karena `d-none` = `display:none !important` akan membuat modal tak terlihat meski sudah dibuka.
- Stok ditampilkan sebagai angka polos (tanpa badge status) karena `criticalThreshold` hanya dikirim `StaffController::index()`, bukan `ManagerController::index()`. Menambahkannya berarti mengubah signature controller tanpa perlu.

### 2. Perubahan
- `resources/views/layouts/app.blade.php`: CSS baru `.produk-detail-media`, `.produk-detail-photo` (320x220, `object-fit: contain`), `.produk-detail-fallback` (kotak "belum punya foto"), `.produk-detail-deskripsi` (`white-space: pre-wrap` supaya baris baru deskripsi tidak collapsed), dan `.produk-detail-kosong` (untuk nilai yang terisi `-`). Kelas `.produk-overlay/-box/-head/-body/-foot` dan `.produk-preview` tidak diubah.
- `resources/views/staff/dashboard.blade.php`:
  - Tombol `Lihat` berubah dari `<a href="...">` menjadi `<button type="button" class="btn-lihat-produk">` dengan atribut `data-lihat-produk`, `data-nama`, `data-kategori`, `data-type`, `data-size`, `data-color`, `data-harga`, `data-stok`, `data-deskripsi`, `data-gambar`. `href`, `target`, dan `rel` dihapus. `data-harga` sudah diformat `Rp 150.000` di Blade.
  - Overlay `#lihatProdukOverlay` ditambahkan (9 field + 1 tombol Tutup).
  - IIFE JS baru `openLihatOverlay()` / `closeLihatOverlay()`: isi lewat `textContent`, tutup lewat tombol Tutup, klik backdrop, atau `Escape`; fokus pindah ke tombol Tutup saat dibuka dan kembali ke tombol pemicu saat ditutup; scroll `<body>` dikunci selama overlay terbuka.
- `resources/views/admin/dashboard.blade.php`: sama persis, dengan prefix `lihatProdukAdmin*` dan `class="btn-lihat-produk-admin"` (tetap ikon-saja, tanpa teks, karena kolom admin sudah padat).
- `app/Http/Controllers/ManagerController.php`: komentar basi di `index()` dikoreksi dari "menambah, mengubah, dan menghapus" menjadi "mengubah dan menghapus", karena `storeProduct()` sudah dihapus pada revisi sebelumnya. Tidak ada perubahan perilaku.

### 3. Batas yang disepakati
- Tidak ada migration, tidak ada perubahan route, dan halaman publik `/toko/produk/{id}` tidak disentuh (masih bisa diakses lewat URL).
- Tidak ada tombol "Buka halaman publik" di dalam modal, sesuai permintaan tidak berpindah halaman.
- Overlay box masih `background: #fff` hardcoded sehingga di dark mode tetap terang. Masalah ini **sudah ada** pada overlay Edit dan sengaja tidak diperbaiki di sini agar perubahan tidak melebar.

### 4. Hasil verifikasi (tanpa `php artisan test`)
Probe render read-only lewat HTTP kernel terhadap `/staff/dashboard` dan `/admin/dashboard` (role staff & admin), memakai data yang sudah ada tanpa insert/update/delete sama sekali: **144 pemeriksaan, 144 lolos**.
- 11 assertion negatif `StaffDashboardTabTest` (L98-104, L108, L136, L142-143) tetap absen, termasuk `name="stock"`, `data-reopen`, dan `bootstrap.Modal.getOrCreateInstance`.
- Hitungan presisi utuh: `dashboard-section` = 5, `dashboard-section d-none` = 4, `logout` = 1, `class="btn btn-outline-danger"` masih ada, `data-product-row` = jumlah produk, `data-search` tidak berubah.
- Kedua halaman **sudah tidak lagi** memuat `href` ke `/toko/produk/...` maupun `target="_blank"` di tabel produk.
- Bukti read-only (dipotong per blok overlay): tidak ada `<form>`, `<input>`, `<textarea>`, `<select>`, `type="submit"`, maupun kata "Simpan"/"Hapus"; hanya ada satu tombol Tutup; `role="dialog"` + `aria-modal="true"` ada.
- Atribut `data-lihat-produk` muncul tepat satu kali per produk di kedua halaman; `data-harga` conforms `Rp <angka>`, `data-stok` conforms angka bulat.
- Deskripsi panjang produk #3 (245 karakter) utuh di `data-deskripsi` kedua halaman, jadi escaping Blade tidak merusak isi.
- Overlay Edit di kedua halaman tetap utuh (form, `@csrf`, `method="POST"`, jumlah tombol Edit per produk) - tidak ada regresi.
- Data dan berkas tidak berubah: 4 produk, 4 kategori, 0 favorit, 0 `transaction_details`, 6 restock, 0 `image` non-null, 0 berkas di `public/images/produk/`.
- Perilaku JS (buka/tutup, Escape, perpindahan fokus) **belum diverifikasi di browser sungguhan** - batasan yang sama seperti Phase 16/17 dan sudah tercatat di `todo.md`.

---

## 2026-10-07 - Phase 18: Landing Page "La Mamora" (Adaptasi Template Statis "Wearly")

Permintaan: adaptasi file HTML statis landing page "Wearly" menjadi halaman `/` (welcome) di proyek Laravel ini, dengan brand, data, dan autentikasi sesuai aplikasi nyata. Keputusan yang disepakati: brand **La Mamora**, modal login/register **terhubung ke backend**, koleksi produk **diambil dari database**, pekerjaan selesai dicatat di `changelog.md` + `todo.md`.

### 1. Perubahan berkas
- `routes/web.php`: closure route `/` ditulis ulang - mengimpor `App\Models\Category/Product/User`, mengambil `Product::with('category')->latest()->get()`, `Category::orderBy('name')->get()`, dan `$stats` (jumlah produk / jumlah pelanggan / jumlah kategori), lalu `return view('welcome', compact(...))`. Diperiksa dengan Pint (`fully_qualified_strict_types`, `statement_indentation`, `ordered_imports` diperbaiki) sehingga bersih.
- `resources/views/welcome.blade.php`: **ditulis ulang total** (23.851 byte) memakai struktur/animasi/JS dari template Wearly, isinya:
  - Brand **La Mamora** di judul, navbar, footer (menggantikan "Wearly"/"TPS Pakaian Store"); navbar `@guest` menampilkan tombol Masuk/Daftar, `@auth` menampilkan tombol ke dashboard sesuai role (`/toko` pelanggan, `/staff/dashboard` staff, `/admin/dashboard` admin).
  - Section hero + mockup ponsel, About + statistik angka, Koleksi, Kontak, Blog, Footer.
  - **Koleksi dari database**: tab kategori dari `Category::orderBy('name')`, kartu produk dari `$products` (`@forelse`) dengan `image_url` atau fallback emoji via closure `$emojiFor`, harga `number_format(..., 0, ',', '.')`, badge "Stok Habis" bila `stock <= 0`, atribut `data-kategori` untuk filter client-side, tombol `+` = tamu -> buka modal login + toast, sudah login -> `route('toko.index')`.
  - **Modal auth terhubung backend**: `<dialog>` berisi dua tab yang menaungi form `POST route('login')` dan `POST route('register')` lengkap `@csrf`; register menyertakan `password_confirmation`; `@error` ditampilkan per field; saat `old('_tab')` / `$errors->any()` modal dibuka kembali otomatis pada tab yang benar (redirect `back()->withErrors()` dari `AuthController` mempertahankan `_tab`).
  - Toast untuk `session('success')` + `@error('credentials')`; section Kontak menampilkan toast demo (bukan pengiriman email).
- `tests/Feature/ExampleTest.php`: menambahkan `use RefreshDatabase;` (baris import sebelumnya dikomentari). Route `/` kini meng-query tabel `products`, sehingga test `GET /` yang tadinya lolos tanpa migrasi kini butuh schema sqlite in-memory. Migrasi `fix_transactions_foreign_keys_and_columns` aman di sqlite karena semua `DB::statement` MySQL-specific dibungkus try/catch.
- `tests/Feature/StaffDashboardTabTest.php`: asersi baris 179 diubah `<option value="1" selected="selected">` -> `<option value="1" selected>`. Directive `@selected` Blade milik Laravel versi ini meng-`echo 'selected'` polos (`CompilesConditionals.php`), bukan `selected="selected"` - assertion ini sudah basi sejak view memakai `@selected` dan **gagal bahkan sebelum Phase 18 dimulai** (dikonfirmasi lewat baseline `git stash`).

### 2. Hasil verifikasi
- `php artisan test`: **7 test, 7 lolos, 98 assertion** (baseline sebelum perubahan: 6 lolos / 1 gagal - `StaffDashboardTabTest::test_tambah_produk...`). Tidak ada test baru yang gagal; satu failure pre-existing ikut diperbaiki.
- `.\vendor\bin\pint --test routes\web.php resources\views\welcome.blade.php tests\Feature\ExampleTest.php`: **passed**. (`pint --test` terhadap seluruh repo masih gagal di 22 berkas lama yang tidak disentuh oleh Phase 18 - pelanggaran gaya pre-existing: `AuthController`, model, migrasi, `routes/api.php`, dll.)
- Probe HTTP (`GET /` lewat kernel, tanpa menulis data): **28/29 lolos, HTTP 200, 23.851 byte**; satu-satunya kegagalan adalah "badge stok habis" yang **diharapkan** karena keempat produk di DB punya stok > 0. Tidak ada sisa `{{` / direktif Blade tak ter-render di output.
- Route `/` yang sebelumnya tanpa query DB kini membaca 4 produk / 4 kategori / 4 pelanggan / 3 kategori (angka `$stats`).

### 3. Batasan yang disepakati
- Perilaku JS landing (filter kategori, animasi reveal, pembukaan modal) **belum diuji di browser sungguhan** - sama seperti batasan Phase 16/17.
- Form Kontak hanya menampilkan toast demo, tidak mengirim email.
- Halaman `/login` dan `/register` (view Blade terpisah) tidak diubah - modal di landing adalah lapisan tambahan.
- Tidak ada perubahan route/method auth: tetap `AuthController` (`password => confirmed`, role register di-hardcode `pelanggan`, redirect per role).
- Tidak ada perubahan data: 4 produk, 4 kategori, tidak ada migrasi dijalankan.
- Laravel Boost (instruksi `AGENTS.md`) tidak dipasang demi tidak mengubah `composer.json` untuk tugas UI ini.

### Revisi Phase 18 (1): Tab Login Modal Landing Cukup Email + Password

Permintaan: pengguna hanya perlu memasukkan **email dan password** saat login dari landing page.

- **Akar masalah**: field Nama (`#nameF`) dan Konfirmasi Password (`#confF`) memang sudah diberi atribut `hidden` oleh `setTab()` JS saat tab Login, tetapi CSS di `welcome.blade.php:125` (`label{...display:grid...}`) berasal dari origin author sehingga **menimpa** `[hidden]{display:none}` milik user-agent stylesheet. Akibatnya kedua field tetap terlihat (modal menampilkan 4 field, bukan 2).
- **Perbaikan**: 1 baris CSS baru di `resources/views/welcome.blade.php:126` - `label[hidden]{display:none}` (spesifisitas 0,1,1 menang melawan rule `label` 0,0,1).
- **Tidak berubah**: tab Register (tetap Nama, Email, Password, Konfirmasi Password sesuai validasi `AuthController`), JS `setTab()`, route, controller, dan backend login (memang sudah hanya menerima `email` + `password`).
- **Verifikasi**: tidak menjalankan test apa pun (permintaan eksplisit pengguna); diverifikasi dengan membaca ulang file hasil edit - rule ada di baris 126, dan satu-satunya label beraatribut `hidden` di halaman ini memang `#nameF`/`#confF` (welcome.blade.php:359, 371) yang ditoggle oleh JS baris 426/428.


---

## 2026-10-07 - Phase 19: MIS Analytics Dashboard Manager/Admin

Permintaan: kembangkan backend & frontend Sistem Informasi Manajemen (MIS) pada dashboard Manager/Admin memakai tabel yang sudah ada (`transactions`, `transaction_details`, `products`, `restock_requests`, `users`, `employees`), tanpa menjalankan satu pun command test.

### 1. Backend - `app/Http/Controllers/ManagerController.php` (method `index()`)
- **Periode laporan (Custom Report Generator)**: baca query `dari`/`sampai` (divalidasi `Carbon::hasFormat`), default **6 bulan terakhir s/d hari ini**; rentang terbalik otomatis ditukar. Hasil dikirim sebagai `$periode` (label + nilai input).
- **Executive Summary KPI** (`$kpi`):
  - Total Omset: `SUM(total)` dari `transactions` pada periode filter (kolom asli bernama `total`, bukan `total_price`).
  - Total Produk Terjual: `SUM(quantity)` dari `transaction_details` lewat `whereIn(transaction_id, ...)` periode yang sama.
  - Stok Kritis: `Product::where('stock','<',5)->count()`.
  - Restock Pending: `RestockRequest::where('status','pending')->count()`.
- **Grafik tren penjualan bulanan**: transaksi periode diambil lalu **di-group per bulan di PHP** (DB-agnostic - tidak memakai `DATE_FORMAT`/`strftime`), bulan kosong di-zero-fill (maks 36 bulan) -> `$trenLabels`, `$trenOmset`, `$trenTerjual`.
- **Top 5 produk terlaris**: agregat `transaction_details` (`SUM(quantity)` + `SUM(subtotal)`) di-group per `product_id`, diurut turun, limit 5, nama produk di-resolve lewat `Product::whereIn(...)->keyBy('id')` (tanpa N+1).
- **Exception Reporting**: `$lowStock = Product::with('category')->where('stock','<',5)->orderBy('stock')->get()`.
- **Audit Trail**: `$auditTrail = RestockRequest::with(['product','employee'])->latest()->limit(15)->get()` (nama pengaju + posisi + status approval).
- Method lain (`updateStatus`, `updateProduct`, `destroyProduct`) dan route **tidak berubah**.

### 2. Frontend - `resources/views/admin/dashboard.blade.php`
- **Chart.js 4.4.1 UMD via CDN** (`@push('scripts')`, diinisialisasi hanya bila elemen canvas ada dan `Chart` terdefinisi).
- **Section `#analisis-mis`**: form filter tanggal GET (`dari`, `sampai`, tombol Terapkan/Reset), tombol **Cetak/Export `window.print()`**, 4 kartu KPI (Omset Rp, Produk Terjual, Stok Kritis, Restock Pending), **Bar chart Omset + Line chart Produk Terjual** (dual sumbu Y) untuk tren bulanan, **Doughnut Top 5** + tabel ringkas (bila `transaction_details` kosong: placeholder, canvas tidak dirender).
- **`#stok-realtime` Low Stock Alert**: tabel produk stok < 5 pcs (badge Habis/Kritis); kosong -> alert hijau "Semua stok aman".
- **`#riwayat-stok` Audit Trail**: tabel Tanggal | Produk | Pengaju (employee + posisi) | Jumlah | Status Approval | Catatan - sekaligus memperbaiki dua link sidebar admin yang sebelumnya mengarah ke anchor tidak ada (`#stok-realtime`, `#riwayat-stok`).
- **CSS `@media print`** (`@push('styles')`): sembunyikan sidebar, form, tombol, overlay; tampilkan `.print-header` "Laporan MIS - La Mamora (periode + waktu cetak)".
- **Form "Tambah Master Produk Baru" dipastikan tetap absen** di dashboard Admin (sudah tidak ada sejak Revisi Phase 17; tidak ada yang perlu dihapus) - Admin fokus Monitoring Stok, Approval, dan Analisis MIS.

### 3. Logout di menu Pengaturan - `resources/views/layouts/sidebar.blade.php`
- Modal `#settingsModal` kini memuat **form POST `route('logout')`** dengan tombol "Logout Akun" `btn btn-outline-danger` (menggantikan teks "Fitur pengaturan akan segera hadir").
- Markup modal **dibungkus `@if(Auth::user()->role !== 'staff')`** karena partial sidebar ikut ter-render di halaman Staff, dan `StaffDashboardTabTest:109` mensyaratkan tepat **1** form logout di HTML halaman staff. Link sidebar `data-bs-target="#settingsModal"` untuk admin tetap ada (diuji test baris 228).

### 4. Hasil verifikasi (TANPA `php artisan test` - permintaan eksplisit)
- `php -l app/Http/Controllers/ManagerController.php` -> No syntax errors (lint cepat, bukan test).
- Pembacaan ulang struktur view: section id lengkap (`analisis-mis`, `stok-realtime`, `kelola-produk`, `persetujuan-restock`, `riwayat-stok`), `@section/@endsection` dan `@push/@endpush` berpasangan.
- Constraint test yang dipatuhi dari pembacaan kode test: logout staff tetap 1x, admin tetap punya `#settingsModal` + `href="#riwayat-stok"` + `>Riwayat Stok</span>`, tanpa `data-bs-toggle="tab"`, tanpa `sidebar-footer`.
- Data nyata saat pengembangan: 6 transaksi (semua September 2026), `transaction_details` 0 baris, stok produk 74/19/10/28 (tidak ada yang < 5) - sehingga KPI "Produk Terjual" = 0, Top 5 kosong (empty state), dan Low Stock Alert menampilkan "Semua stok aman". Semua kondisi kosong sudah ditangani tampilan.

---

## 2026-10-07 - Phase 19 Revisi: Detail Transaksi (Checkout) + TransactionDetailSeeder

Masalah: `transaction_details` kosong (0 baris) sehingga KPI "Produk Terjual" = 0 dan doughnut "Top 5 Produk Terlaris" selalu empty state di dashboard MIS.

### 1. Perbaikan checkout - `app/Http/Controllers/TokoController.php::buyProduct()`
- Pembelian kini disimpan **atomik** dalam `DB::transaction()`: kurangi stok -> `Transaction::create()` -> **`$transaction->details()->create(['product_id','quantity','subtotal'])`** lewat relasi hasMany `Transaction::details()` yang sudah ada. Tabel & model `TransactionDetail` sudah tersedia - tanpa migration.
- `subtotal = price * quantity` (sama dengan `total` transaksi karena tiap pembelian = 1 produk; tidak ada tabel keranjang di skema ini, jadi 1 baris detail per pembelian - bila keranjang multi-item ditambah nanti cukup loop per item).
- Hardening di method yang sama: `$quantity = max(1, (int) ...)` dan cek stok `$product->stock < $quantity` (sebelumnya hanya `<= 0`, sehingga qty besar lolos padahal stok tidak cukup).

### 2. Seeder - `database/seeders/TransactionDetailSeeder.php`
- Mengisi detail untuk transaksi ID 1-6 dengan pemetaan yang subtotal-nya **persis sama** dengan kolom `total`:
  - Trx 1: 2 x Kemeja Hitam Polos = Rp 300.000
  - Trx 2/3/4: 1 x Kemeja Hitam Polos = Rp 150.000
  - Trx 5: 1 x Jeans Modern + 1 x Gaul Totebag = Rp 140.000
  - Trx 6: 1 x Jeans Modern = Rp 90.000
- Subtotal dihitung dari harga produk saat runtime (bukan angka mati), `created_at/updated_at` = `date` transaksi, **idempoten** (transaksi yang sudah punya detail dilewati), tiap baris melapor "cocok/berbeda" dari `transactions.total`.

### 3. Hasil (TANPA menjalankan test - hanya perintah seed + query baca)
- `php artisan db:seed --class=TransactionDetailSeeder` -> 6/6 transaksi **"detail cocok dengan total"**.
- Run kedua -> semua "sudah punya detail, dilewati" (idempoten terbukti).
- Query verifikasi: **7 baris** `transaction_details`, per-trx `SUM(quantity)` = 2/1/1/1/2/1, dan `withSum('details','subtotal')` == `total` untuk keenam transaksi (300000, 150000, 150000, 150000, 140000, 90000).
- Efek ke MIS: KPI "Produk Terjual" dan doughnut Top 5 kini punya data (Kemeja Hitam Polos memimpin dengan 5 pcs / Rp 750.000).

---

## 2026-10-07 - Phase 20: Sidebar & Section-Switching Dashboard Manager (ala Staff)

Permintaan: menu sidebar kiri dashboard Manager dibuat seperti dashboard staff - menu "Dashboard" hanya menampilkan sekilas beberapa tampilan (ditambah analisis penjualan MIS), sedangkan tampilan penuh baru muncul saat menekan menu di sidebar.

### 1. `resources/views/layouts/sidebar.blade.php` (cabang admin saja)
- Link **Dashboard** kini `href="{{ route($dashboardRoute) }}#dashboard"` (diberi hash agar pemilih section mengenali menu ini).
- Link baru **"Analisis Penjualan"** -> `{{ route('admin.dashboard') }}#analisis-mis` (ikon `bi-graph-up-arrow`) tepat setelah Dashboard, hanya di cabang `@else` (non-staff).
- Link OAS (`#stok-realtime`, `#kelola-produk`, `#persetujuan-restock`, `#riwayat-stok`) TIDAK disentuh - bentuk `route('admin.dashboard')#xxx` dipertahankan persis untuk assertion `StaffDashboardTabTest:229,231`. Link Profil/Pengaturan (modal) juga tidak disentuh.

### 2. `resources/views/admin/dashboard.blade.php` - dipecah jadi 6 section `.dashboard-section`
| Section | Isi | Status awal |
|---|---|---|
| `#dashboard` | 4 kartu KPI (Omset, Produk Terjual, Stok Kritis, Restock Pending) + 3 widget sekilas: **Stok Kritis** (top 3 `$lowStock` -> link `#stok-realtime`), **Restock Pending** (top 3 `$restockRequests->where('status','pending')` -> link `#persetujuan-restock`), **Top Produk MIS** (top 3 `$topProduk` -> link `#analisis-mis`); tiap widget punya link "Lihat selengkapnya ->" | aktif (default) |
| `#analisis-mis` | Card MIS: header + badge periode + **tombol Cetak** (dipindah dari header halaman), form filter tanggal GET, grafik tren dual-sumbu + doughnut Top 5 + tabel ringkas (KPI dikeluarkan ke `#dashboard`) | `d-none` |
| `#stok-realtime` | Low Stock Alert - **di-flatten jadi sibling** (sebelumnya bersarang di dalam `#analisis-mis`, yang akan ikut tersembunyi saat section induk `d-none`) | `d-none` |
| `#kelola-produk` / `#persetujuan-restock` / `#riwayat-stok` | konten lama tanpa perubahan, hanya tambah class `dashboard-section d-none` | `d-none` |
- Overlay edit/lihat produk tetap di LUAR section (posisi fixed, dan reopen-on-error setelah validasi gagal tetap jalan karena redirect membawa hash `#kelola-produk`).
- `.print-header` tetap di luar section; tombol Cetak kini hanya ada di card MIS (cetak = view aktif; via `Ctrl+P` pun hanya section aktif yang tercetak karena section lain `display:none`).

### 3. JavaScript pemilih section (pola identik dengan `staff/dashboard.blade.php:779-822`)
- `sections` = semua `.dashboard-section`; `navLinks` = link `.sidebar-nav .nav-link` yang hash-nya menunjuk ke section nyata -> link Profil/Pengaturan **tidak ikut `preventDefault`**, trigger modal Bootstrap tetap hidup (lebih ketat daripada versi staff).
- `activateSection(id)`: toggle `d-none`, toggle class `.active` via `href.endsWith('#'+id)` (admin memakai URL penuh), `history.replaceState('#'+id)`, lalu `Chart.getChart(canvas).resize()` pada semua canvas section (canvas dibuat saat container masih `display:none`).
- Klik link -> `preventDefault` + pindah section + tutup sidebar mobile; `hashchange` + load awal -> `sectionFromHash()`.
- Default saat load: hash valid -> pakai hash; ada query string (baru submit filter tanggal) -> `#analisis-mis`; selain itu -> `#dashboard`.
- Link header "Kelola Produk" dan link widget "Lihat selengkapnya" (di luar `.sidebar-nav`) sengaja dibiarkan sebagai anchor biasa - perpindahan hash memicu listener `hashchange`, jadi tetap tanpa reload.

### 4. Verifikasi (TANPA `php artisan test` - permintaan eksplisit)
- `php artisan view:cache` -> "Blade templates cached successfully" (semua view termasuk admin/dashboard.blade.php kompilasi tanpa error), lalu `view:clear`.
- Hitungan `<div>`/`</div>` admin view seimbang 127/127; staff view tak tersentuh (139/139).
- Grep struktur: tepat 6 section `.dashboard-section` di admin view, `window.print()` hanya 1 (di card MIS), `@push/@endpush` berpasangan.
- Constraint `StaffDashboardTabTest:228-235` dicek via grep di `sidebar.blade.php`: `data-bs-target="#settingsModal"` (1), `href="#persetujuan-restock"` (1), `href="#riwayat-stok"` (3), `>Riwayat Stok</span>` (2), `data-bs-toggle="tab"` (0), `sidebar-footer` (0), `#riwayat-restock"` (0).
- Catatan: uji browser (klik menu, resize chart, filter, cetak) belum dilakukan.

---

## 2026-10-07 - Phase 21: Rebrand "TPS Pakaian" -> "La Mamora" (Logo + Teks View)

Permintaan: ganti semua logo TPS Pakaian dengan logo La Mamora seperti di landing page. Keputusan pengguna: cakupan = logo + semua teks branding di view (ChatbotSeeder/DB tidak disentuh), warna kotak "L" = navy `#14213d` persis landing (`welcome.blade.php:26`).

### 1. Logo visual (3 spot, markup identik landing `<i>L</i>La Mamora.`)
- `layouts/sidebar.blade.php` - `<i class="bi bi-shop...>` + "TPS Pakaian" -> **`<i class="lm-mark">L</i><span>La Mamora.</span>`** (badge role tetap).
- `layouts/app.blade.php` - CSS baru `.sidebar-brand .lm-mark`: kotak 30x30px, radius 9px, bg `#14213d`, huruf putih bold 15px (ukuran = persis `.logo i` landing).
- `layouts/toko-shell.blade.php:157` dan `toko/index.blade.php:199` - `<span class="logo">[glyph flourish]</span>TPS Pakaian` -> **`<span class="logo">L</span>La Mamora.`**
- CSS `.logo` di kedua file tersebut: `background:var(--accent);color:var(--on-accent)` -> **`background:#14213d;color:#fff;font-weight:800`** (ukuran tetap 38px menyesuaikan surface).

### 2. Teks branding (14 occurrence -> La Mamora)
- `layouts/app.blade.php:7` default judul: `TPS & OAS Pakaian` -> `La Mamora - OAS Pakaian`.
- `layouts/toko-shell.blade.php:7` judul: `{{ $title ?? 'TPS Pakaian' }} - TPS Store` -> `{{ isset($title) ? $title . ' - ' : '' }}La Mamora - Toko Outfit Official` (default = judul landing, tanpa duplikasi nama saat `$title` di-pass view).
- `toko-shell:193` heading default -> `La Mamora`.
- Chatbot (`toko-shell:266,271,281` + `toko/index:425,430,440`): aria-label, judul jendela, dan sapaan -> `Asisten Virtual La Mamora` / `asisten virtual La Mamora`.
- Judul halaman: `toko/index:7` `Katalog Pakaian - La Mamora`, `toko/pesanan:7` `Pesanan Saya - La Mamora`, `auth/login:5` `Login - La Mamora` + heading `:14` `Login La Mamora`, `auth/register:5` `Daftar Akun Pembeli - La Mamora`.

### 3. Di luar cakupan (dicatat di todo.md)
- `ChatbotSeeder` - 2 jawaban bot di DB masih menyebut "TPS Pakaian" (data existing, sesuai pilihan pengguna tidak diubah; sapaan view sudah La Mamora -> inkonsistensi kecil bila user menanyakan identitas toko).
- `public/favicon.ico` - tidak ada asset logo La Mamora; landing juga tidak set favicon.
- Judul view yang di-pass controller (mis. "Dashboard Manager - OAS Approval") - tidak mengandung "TPS", tidak diubah.

### 4. Verifikasi (TANPA `php artisan test` - permintaan eksplisit)
- Grep case-sensitive `TPS` di seluruh `resources/views` -> **0 hasil**.
- Grep `La Mamora` -> 24 spot (welcome 5, admin 1, login 2, register 1, app 2, sidebar 1, toko-shell 7, index 5, pesanan 1).
- `php artisan view:cache` -> "Blade templates cached successfully", lalu `view:clear`.
- Baca ulang area logo: sidebar `lm-mark` + teks, CSS `.logo` navy di toko-shell & index, brand `L La Mamora.` ketiganya terkonfirmasi.
- Catatan: uji browser (tampilan kotak L di tiap surface) belum dilakukan.

---

## 2026-10-21 - Phase 22: Design System Dashboard La Mamora (satu sumber token)

Permintaan: dashboard admin dan staff diselaraskan dengan token visual landing page Phase 18, token dijadikan satu sumber (file CSS bersama), dan modul DSS Phase 23-28 dibangun di atasnya. Keputusan pengguna: penomoran fase **22-28**, **middleware role baru** dibuat sendiri, **Laravel Boost dilewati**, **file CSS statis** dipakai (bukan Vite/Tailwind).

### 1. Perubahan berkas
- **`public/css/la-mamora.css` (baru, ~22,7 KB)** - design system sebagai satu sumber token:
  - Block `:root` token canonical berprefiks `--lm-*`: navy `#14213d`, sky `#e3eefb`, sky-2 `#f6faff`, orange `#ff6b2c`, muted `#5f6b7f`, line `#dbe5f3`, white `#fff` (nilai **persis** sama dengan landing page Phase 18), plus status (success/danger/warning/info), tipografi (Plus Jakarta Sans), radius (9/12/18/22/99), shadow, spasi, lebar sidebar/topbar, dan z-index.
  - Block alias `:root` (`--navy`, `--sky`, `--sky2`, `--orange`, `--muted`, `--line`, `--white` -> `var(--lm-*)`) supaya landing page yang masih memakai nama token lama tidak perlu ditulis ulang - nilainya tetap tinggal di satu file ini.
  - Override variabel Bootstrap 5.3 (`--bs-body-*`, `--bs-primary*`, `--bs-warning*`, `--bs-danger*`, `--bs-success*`, `--bs-info*`, `--bs-border-radius*`, `--bs-focus-ring-color`, dll) - palet Bootstrap diarahkan ke token La Mamora; nilai `-rgb` ikut diatur agar utility `bg-opacity-10` tetap bekerja seperti semula.
  - Komponen `.lm-*`: `.lm-shell`, `.lm-topbar` (+`.lm-topbar-eyebrow/.lm-topbar-title/.lm-topbar-meta/.lm-topbar-toggle`), `.lm-chip`, `.lm-stat-card/.lm-stat-icon/.lm-stat-label/.lm-stat-value/.lm-stat-note` (+ varian `is-orange/is-success/is-danger/is-warning/is-info`), `.lm-badge` (`lm-badge-pending/approved/rejected/info/neutral/rank`), `.lm-empty`, `.lm-table-wrap`, `.lm-section-title/.lm-section-desc`, `.lm-form-grid`, `.lm-matrix` (matriks perbandingan AHP), `.lm-score/.lm-score-bar`, `.lm-criterion`, `.lm-metric-row/.lm-metric`, `.min-w-0`, `.sidebar-overlay`.
  - Override komponen Bootstrap **di-scope `body.lm-dashboard`** (button, card, table, badge, form, alert, `.bg-light`, `.table-light`, `.btn-warning` -> bg orange + teks navy) agar landing page dan dashboard pelanggan tidak ikut berubah.
  - Blok layout dashboard: `.sidebar` (navy border + shadow), `.sidebar-header`, `.sidebar-brand .lm-mark` (kotak "L" navy 30px - sama persis dengan landing), `.sidebar-nav .nav-link` (+ state active/hover), dan aturan mobile (sidebar off-canvas + `.sidebar-overlay`).
- **`resources/views/layouts/app.blade.php`** - `<body class="lm-dashboard">` (mengaktifkan tema), link `<link href="{{ asset('css/la-mamora.css') }}">`, topbar baru (tombol `#sidebarToggler` + judul `#lmTopbarTitle` + `.lm-chip` tanggal/peran), CSS overlay produk di-tokenisasi (`var(--lm-white)` dll), dan JS baru: judul topbar mengikuti section aktif lewat atribut `data-lm-title` + `MutationObserver` (class `d-none` section ditukar).
- **`resources/views/admin/dashboard.blade.php`** - 6 section diberi `data-lm-title` ("Dashboard Manager", "Analisis Penjualan", "Monitoring Stok Realtime", "Kelola Produk", "Persetujuan Restock", "Riwayat Stok"); 4 kartu KPI memakai `.lm-stat-icon/.lm-stat-label/.lm-stat-value/.lm-stat-note` (ikon bulat navy/soft-tint + label kapital + angka tabular).
- **`resources/views/staff/dashboard.blade.php`** - 5 section diberi `data-lm-title` ("Dashboard Staff", "Monitoring Stok", "Pengajuan Restock", "Riwayat Stok", "Pengaturan"); 3 kartu statistik memakai kelas `.lm-stat-*` yang sama. **Class `dashboard-section` tidak ditambah/diubah** agar assertion `StaffDashboardTabTest` (5 section, 4 `d-none`) tetap lolos; `data-lm-title` diletakkan setelah atribut `id`.
- **`resources/views/welcome.blade.php`** - blok `:root{}` inline dihapus (nilainya kini dari `la-mamora.css`), link CSS bersama ditambahkan. Landing **tidak** didesain ulang; markup dan kelas `.btn/.card` miliknya tetap menang karena `<style>` inline-nya dimuat setelah `<link>`.

### 2. Hasil verifikasi
- `php artisan test` -> **7/7 lolos, 98 assertions** (identik dengan baseline sebelum Phase 22; `StaffDashboardTabTest` 5 method tetap hijau termasuk hitungan section, 1x form logout, badge Pending, dan `Master Data Terdaftar`).
- `php artisan view:cache` -> "Blade templates cached successfully" (semua view kompilasi tanpa error), lalu `view:clear`.
- Probe HTTP (server bawaan `php artisan serve`): `GET /` -> **200** (24.375 byte, memuat `<link .../css/la-mamora.css>`), `GET /css/la-mamora.css` -> **200** (22.696 byte, token `--lm-navy: #14213d` terbaca).
- `vendor\bin\pint --test` -> gagal di **22 berkas pre-existing** (AuthController, model, migrasi lama, `routes/api.php`, dll) yang **tidak disentuh** Phase 22; 0 berkas yang disentuh Phase 22 masuk daftar failure.
- `git diff --stat` Phase 22: 4 view berubah (+111/-161) + 1 file baru `public/css/la-mamora.css`; tidak ada perubahan skema/data.

### 3. Batasan yang disepakati
- **Laravel Boost tidak dipasang** (keputusan pengguna) - `AGENTS.md` masih mewajibkan instalasi Boost untuk sesi berikutnya; `composer.json` tidak diubah.
- Token dikirim lewat **file CSS statis** `public/css/la-mamora.css` (tanpa Vite/Tailwind/build step) - file harus ikut ter-deploy di folder `public/`.
- Override Bootstrap di-scope `body.lm-dashboard` -> **landing page** dan **dashboard pelanggan** (`layouts/toko-shell.blade.php`, `toko/*`) tidak berubah visual; katalog pelanggan masih memakai palet biru Phase 15/16 dan tidak disentuh di Phase 22.
- Halaman staff **tidak** menambah section baru (harus tetap 5) dan **tidak** menambah form logout kedua; UI DSS staff karena itu akan dibuat sebagai **halaman terpisah** (Phase 26), bukan section tambahan.
- Belum ada UI DSS yang dibuat di Phase 22 - file ini hanya menyiapkan fondasi token + komponen untuk Phase 23-28.
- Belum diverifikasi di browser (tampilan akhir dashboard admin/staff, responsivitas mobile, dan perpindahan judul topbar) - hanya probe HTTP dan uji otomatis.

---

## 2026-10-21 - Phase 23: Database Decision Support System (DSS)

Permintaan: fondasi data DSS untuk AHP (pembobotan kriteria), VIKOR (peringkat pemasok), dan rekomendasi restock. Semua perubahan skema dibuat lewat **migration baru** - tidak ada tabel/kolom existing yang dihapus atau diubah tipe datanya.

### 1. Perubahan berkas
- **8 migration baru** (prefix `2026_10_21_`):
  - `090000_create_suppliers_table` - `suppliers`: name, code (unique), contact_name, phone, email, address, city, **lead_time_days**, **min_order_qty**, rating (0-5), is_active, notes.
  - `090100_create_product_suppliers_table` - `product_suppliers`: FK `products` + `suppliers` (cascade), harga beli, min_order_qty, lead_time_days, is_preferred, notes, unique(product_id, supplier_id).
  - `090200_create_dss_criteria_table` - `dss_criteria`: code (unique), name, description, **type** (benefit/cost), weight (hasil AHP, nullable), is_active, sort_order.
  - `090300_create_dss_criteria_comparisons_table` - `dss_criteria_comparisons`: FK 2 kriteria + `value` (skala perbandingan), unique per pasangan.
  - `090400_create_dss_runs_table` - `dss_runs`: name, type (ahp/vikor/restock), status, created_by -> users, criteria_count, **lambda_max / ci / cr / ri**, is_consistent, payload (JSON).
  - `090500_create_dss_supplier_scores_table` - `dss_supplier_scores`: run + supplier + kriteria + score + weight, unique(run, supplier, kriteria).
  - `090600_create_dss_restock_recommendations_table` - `dss_restock_recommendations`: run, product, supplier, current_stock, avg_daily_sales, lead_time_days, safety_stock, reorder_point, recommended_qty, estimated_cost, status, catatan.
  - `090700_add_dss_columns_to_restock_requests_table` - menambah `supplier_id` + `dss_run_id` (nullable) beserta index. **Constraint FK hanya dipasang di MySQL** (`DB::connection()->getDriverName() === 'mysql'`); SQLite tidak mendukung penambahan FK ke tabel existing, jadi di SQLite kolom sengaja tanpa constraint.
- **7 model baru** di `app/Models/`: `Supplier`, `ProductSupplier`, `DssCriterion`, `DssCriterionComparison`, `DssRun`, `DssSupplierScore`, `DssRestockRecommendation` - lengkap dengan relasi dua arah dan `$casts` (decimal -> float, json -> array).
- **Relasi baru pada model existing**: `Product::productSuppliers()/suppliers()/restockRecommendations()`, `RestockRequest::supplier()/dssRun()`, `User::dssRuns()`.
- **4 factory baru**: `SupplierFactory`, `DssCriterionFactory` (state `benefit()`/`cost()`), `ProductSupplierFactory`, plus `ProductFactory` & `CategoryFactory` (belum ada sebelumnya dan dibutuhkan test).
- **`database/seeders/DssSeeder.php` (baru)** - 5 pemasok (lead time 3-14 hari, MOQ 10-50), 3 penawaran per produk (harga beli 58%-78% dari harga jual, 1 pemasok preferred), 6 kriteria AHP (harga/cost 0.30, kualitas/benefit 0.25, lead_time/cost 0.18, reputasi/benefit 0.12, moq/cost 0.08, layanan/benefit 0.07), dan **30 matriks perbandingan** yang dibangun dari perbandingan bobot acuan sehingga matriks konsisten (CR = 0). Seeder **idempoten** (`updateOrCreate`) dan membuat produk contoh bila tabel produk kosong.
- **`database/seeders/DatabaseSeeder.php`** - mendaftarkan `DssSeeder`.
- **`tests/Feature/DssSchemaTest.php` (baru)** - 4 test: master pemasok + penawaran produk, kriteria + matriks perbandingan, run analisis + skor + rekomendasi (termasuk `needsRestock()` dan `payload` JSON), serta pengajuan restock yang tertaut ke supplier + run DSS.

### 2. Hasil verifikasi
- `php artisan migrate:fresh --seed` dengan env override SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) -> **27 migration semua DONE**, seeder jalan tanpa error. `migrate:fresh` **tidak pernah** dijalankan terhadap MySQL (dilarang `AGENTS.md`).
- `php artisan migrate --force` pada MySQL `praktikmsi_tps_pakaian` -> 8 migration baru **DONE**; data existing utuh: users 4, categories 5, products 5, employees 1, transactions 6, transaction_details 7, restock_requests 7, favorites 0.
- Seeding MySQL (`db:seed --class=DssSeeder`) -> suppliers 5, product_suppliers 15 (5 produk x 3 pemasok), dss_criteria 6, dss_criteria_comparisons 30. Jalankan kedua -> jumlah tidak berubah (idempoten terbukti).
- Konsistensi matriks AHP dihitung ulang dari data tersimpan: bobot hasil = **0.3000, 0.2500, 0.1800, 0.1200, 0.0800, 0.0700** (identik dengan bobot acuan), lambda max = 5.999991, CI = -0.000002, CR = -0.000001 (konsisten; deviasi < 1e-5 hanya karena pembulatan 4 desimal).
- Struktur MySQL diverifikasi via `information_schema`: kolom `supplier_id`/`dss_run_id` ada di `restock_requests` dengan FK ke `suppliers`/`dss_runs` (`nullOnDelete`) dan index masing-masing.
- `php artisan test` -> **11/11 lolos, 119 assertions** (baseline 7/7 + 4 test baru `DssSchemaTest`).
- `vendor\bin\pint --test` -> **passed** untuk seluruh 20 berkas baru/ disentuh Phase 23 (7 model + 8 migration + 5 factory/seeder + 1 test). Kegagalan 22 berkas pre-existing tidak bertambah.

### 3. Batasan yang disepakati
- Data lama **tidak** dihapus dan tabel existing **tidak** diubah strukturnya; penambahan kolom pada `restock_requests` hanya kolom nullable baru.
- Constraint FK `restock_requests` -> `suppliers`/`dss_runs` **hanya ada di MySQL**; di SQLite kolomnya tetap ada tetapi tanpa constraint (batasan driver SQLite untuk tabel existing).
- `product_suppliers` memakai `belongsToMany` polos lewat tabel pivot `product_suppliers`; kolom pivot tambahan (harga, MOQ, lead time) accessed lewat model `ProductSupplier`, bukan lewat `withPivot` di relasi `Product::suppliers()`.
- Skor VIKOR belum ada di database (tabel `dss_supplier_scores` masih kosong) - pengisiannya dikerjakan di Phase 24-26.
- `dss_runs`, `dss_supplier_scores`, dan `dss_restock_recommendations` sengaja dibiarkan kosong setelah seeding; isinya dibuat saat analisis dijalankan.

---

## 2026-10-21 - Phase 24: Service Layer DSS (AHP, VIKOR, Rekomendasi Restock & Produk)
- **Permintaan**: lapisan perhitungan DSS yang bisa dipakai ulang (dipakai controller/UI di Phase 25-26) + unit test **perhitungan manual** agar angka AHP/VIKOR bisa dipertanggungjawabkan di laporan.
- **`app/Services/AhpService.php`** (baru): normalisasi kolom -> bobot = rata-rata baris -> lambda max dari matriks ASLI -> CI -> CR (RI Saaty 1-10, ambang 0,10); pasangan hilang dianggap 1; `analyze()` mengembalikan `criteria_weights` berkunci id kriteria.
- **`app/Services/VikorService.php`** (baru): rumus baku `d_ij = w_j*(f*_j - f_ij)/(f*_j - f-_j)` (f* = max/min sesuai benefit/cost; f* = f- -> d = 0), `S = SUM d_ij`, `R = MAX d_ij` (kecil = baik), `Q = v*(S-S*)/(S--S*) + (1-v)*(R-R*)/(R--R*)` (S*/R* = min, S-/R- = max, penyebut nol -> 0, v default 0,5 dijepit 0..1). Peringkat Q menaik, tie-break S terkecil lalu R terkecil.
- **Klasifikasi baku Opricovic** (DQ = 1/(J-1)): C1 `Q(a2)-Q(a1) >= DQ` (keunggulan), C2 peringkat 1 juga terbaik menurut S atau R (stabilitas) -> `Solusi Tunggal` / `Kompromi Alternatif 1 dan 2` / `Kompromi Semua Alternatif`. Label `Diterima`/`Tidak Diterima` dihapus; flag `has_advantage` & `is_stable` tetap ada; tanpa skor -> ranking kosong (bukan angka nol palsu).
- **`app/Services/RestockRecommendationService.php`** (baru): penjualan harian 30 hari (transaksi `completed`), safety stock = ceil(demand x 0,25), reorder point, pembulatan ke MOQ pemasok, estimasi biaya, `persist()` ke `dss_restock_recommendations`, `toRestockRequest()` (pemasok + run DSS).
- **`app/Services/ProductRecommendationService.php`** (baru): `recommendFor()` / `recommendForGuest()` dengan skor explainable (+3 kategori favorit, +2 terlaris, +1 stok, -2 sudah dibeli) + array `reasons` untuk UI.
- **Test**: 6 berkas baru (AhpService 7, VikorService 12, AhpAnalysis 4, DssPipeline 2, RestockRecommendation 11, ProductRecommendation 8).
- **Verifikasi**: `php artisan test` **51/51 lolos, 269 assertions** (baseline 11/119). AHP manual `[[1,2,4],[1/2,1,2],[1/4,1/2,1]]` -> bobot 4/7, 2/7, 1/7, lambda max 3, CI 0, CR 0; VIKOR manual 3 alternatif -> S 1,0000/0,6266667/0,1200, R 0,6000/0,3600/0,1200, Q -0,4400/-0,3733333/-0,2400 (peringkat A > B > C, ambang 0,5).
- **Probe MySQL**: bobot AHP 0,30/0,25/0,18/0,12/0,08/0,07 (CR 0), VIKOR mengurutkan 5 pemasok (#1 Konveksi Bandung Sentosa, Q -0,2348), restock 5 produk, rekomendasi produk 4 item. `pint` passed untuk 9 berkas Phase 24 (total repo tetap 22 berkas pre-existing gagal).
- **Batas**: `dss_supplier_scores.score` = nilai mentah kriteria (normalisasi & arah benefit/cost di `VikorService`); `dss_runs`/`dss_supplier_scores` belum ditulis service (Phase 25-26); `transactions.customer_id` -> `users.id` di MySQL tapi `customers.id` di SQLite (test memakai id sama); data demo belum ada produk di bawah reorder point (Phase 28); belum diverifikasi di browser.

---

## 2026-10-21 - Phase 25: Middleware Role + Route/Controller DSS (Admin & Staff)
- **Middleware** `app/Http/Middleware/EnsureUserHasRole.php` (baru), alias `role` di `bootstrap/app.php`: `role:admin` dan `role:staff,admin`; belum login -> redirect `login`, role tidak cocok -> **403**.
- **Route** `routes/web.php`: route dashboard staff/admin kini dibungkus middleware role; 5 route DSS baru (`staff.dss`, `staff.dss.scores.store`, `admin.dss`, `admin.dss.suppliers`, `admin.dss.run`).
- **Service** `app/Services/DssAnalysisService.php` (baru): orkestrasi AHP -> VIKOR -> restock; `saveScores()` (run `draft` otomatis + tulis `dss_supplier_scores`), `storeRun()` (tulis `dss_runs` + payload JSON), `criteriaWithWeights()`, `suppliersWithScores()`, `activeScoringRun()`.
- **Controller** `DssScoreController` (staff: isi & simpan skor) dan `DssAnalysisController` (admin: ringkasan, daftar pemasok, jalankan analisis) - pada tahap ini keduanya **JSON** (UI Blade menyusul di Phase 26).
- **Test** 3 berkas baru: `DssRoleAccessTest` (4), `DssScoreControllerTest` (2), `DssAnalysisControllerTest` (2).
- **Verifikasi**: `php artisan test` **68/68 lolos, 454 assertions** (baseline 51/269); `pint --dirty` passed; probe render ke MySQL sukses (5 pemasok, 6 kriteria).
- **Batas**: analisis tidak bisa dijalankan meaningful bila skor pemasok masih kosong (ranking VIKOR kosong, bukan error); skor hanya menempel pada run berstatus `draft`.

---

## 2026-10-21 - Phase 26: UI DSS Admin & Staff (halaman terpisah) + grup nav "DSS"
- **Blade** `resources/views/staff/dss.blade.php`: tab Rekomendasi Restock / Penilaian Supplier / Ranking (read-only), bobot AHP, form skor, status pengajuan, tombol "Ajukan Restock".
- **Blade** `resources/views/admin/dss.blade.php`: 6 tab (Ringkasan, Bobot AHP, Ranking VIKOR, Restock, Pemasok, Riwayat), kartu ringkasan `lm-stat-card`, form matriks AHP (isi segitiga atas, bawah = resiprok), tabel VIKOR S/R/Q, CRUD pemasok + relasi produk, Setujui/Tolak pengajuan.
- **Controller dual mode**: HTML (halaman) vs JSON (`Accept: application/json`) pada `index()`, `summary()`, `run()`, `store()`; POST dari form HTML -> redirect + flash message, API tetap JSON (201).
- **Controller baru** `app/Http/Controllers/DssSupplierController.php`: tambah/ubah/hapus/toggle pemasok (hapus diblokir bila sudah dipakai) + kelola penawaran produk.
- **Route** `routes/web.php` (14 route DSS): `admin.dss.weights.store`, `admin.dss.restock.store`, `staff.dss.restock.store`, `admin.dss.suppliers.*` (JSON lama `admin.dss.suppliers` -> `admin.dss.suppliers.data`).
- **Service** `DssAnalysisService`: `comparisonMatrix()`, `saveComparisons()`, `restockAnalysis()`, `restockRequestFor()`, `latestCompletedRun()`, `activeSupplierCount()`; `storeRun()` kini ikut menyimpan rekomendasi restock.
- **Sidebar** `resources/views/layouts/sidebar.blade.php`: grup nav baru **"DSS"** (admin: Analisis DSS + Penilaian Pemasok; staff: Penilaian Pemasok).
- **Test** `tests/Feature/DssPageRenderTest.php` (11 test) dan `tests/Feature/DssSupplierManagementTest.php` (9 test, baru).
- **Verifikasi**: `php artisan test --filter=Dss` **41/41 lolos, 183 assertions**; probe render MySQL -> admin 185 KB (6 tab), staff 78 KB, landing 28 KB.
- **Batas**: perpindahan tab memakai hash (URL tetap), belum ada paginasi, belum diverifikasi manual di browser; `pint` tidak dijalankan di fase ini sesuai instruksi.

---

## 2026-10-21 - Phase 27: UI Pelanggan - Rekomendasi Produk di Landing Page
- **Controller** `app/Http/Controllers/LandingController.php`: closure route `/` dipindah ke controller agar `ProductRecommendationService` bisa di-inject; route diberi nama `landing`.
- **Blade** `resources/views/welcome.blade.php`: landing dipecah jadi dua seksi - **"Rekomendasi untukmu"** (`id="rekomendasi"`, hanya role pelanggan) dan **"Produk Terlaris"** (`id="terlaris"`, semua pengunjung) + link nav "Terlaris"; kartu produk tetap memakai alasan (`Stok tersedia`, `Produk terlaris`, `Sesuai kategori favorit Anda`) dan jumlah pcs terjual.
- **Service** `app/Services/ProductRecommendationService.php`: tambah `bestSelling()` (produk paling banyak terjual 90 hari, stok > 0); `recommendFor()` (riwayat belanja + kategori favorit) untuk seksi personal, `recommendForGuest()` tetap tersedia untuk backward compatibility.
- **Test** `tests/Feature/LandingRecommendationTest.php` (5 test): section terlaris untuk tamu, produk stok 0 tidak masuk, pelanggan melihat section personal + terlaris, staff tidak melihat section personal.
- **Verifikasi**: `php artisan test --filter=LandingRecommendationTest` **5/5 lolos, 19 assertions**; `ProductRecommendationServiceTest` **8/8 lolos**; probe render MySQL -> tamu 28 KB (4 kartu terlaris), pelanggan 31 KB (4 rekomendasi + 4 terlaris).
- **Batas**: section personal hanya untuk role pelanggan; belum diverifikasi manual di browser; `pint` tidak dijalankan di fase ini sesuai instruksi.

---

## 2026-10-21 - Phase 28: Data Demo DSS (Transaksi 30 Hari + Skor Penilaian Pemasok)
- **Seeder** `database/seeders/DssDemoTransactionSeeder.php` (baru): 30 hari transaksi (2-4 transaksi/hari, 1-3 detail/barang, 2 produk "laris" qty 2-5), 4 akun pelanggan demo (password `password`), stok produk tidak diubah, FK `transactions.customer_id` dideteksi otomatis (MySQL -> `users.id`, SQLite -> `customers.id`), idempoten (dilewati bila periode 30 hari sudah berisi >= 30 transaksi).
- **Seeder** `database/seeders/DssScoreSeeder.php` (baru): skor penilaian pemasok per kriteria pada run draft "Penilaian Pemasok Demo" - `harga` dari rata-rata harga penawaran, `lead_time`/`moq` dari profil pemasok, `kualitas`/`reputasi`/`layanan` dari rating (0-5 dinormalisasi 0-100), idempoten.
- **Migrasi** `2026_10_21_132140_widen_dss_supplier_score_precision.php`: `dss_supplier_scores.score` dilebar dari `decimal(6,2)` ke `decimal(12,2)` supaya harga beli mentah rupiah (mis. 150.000) muat tanpa penyesuaian satuan.
- **Registrasi**: kedua seeder didaftarkan di `database/seeders/DatabaseSeeder.php` (setelah `DssSeeder`).
- **Test** `tests/Feature/DssDemoTransactionSeederTest.php` (3 test) dan `tests/Feature/DssScoreSeederTest.php` (3 test): seeder transaksi (rentang 30 hari, detail per transaksi, idempoten, memunculkan rekomendasi restock) dan seeder skor (skor per pemasok x kriteria, idempoten, ranking VIKOR terbentuk).
- **Verifikasi**: `php artisan test` **84/84 lolos, 510 assertions** (baseline 78/490); `pint --dirty` passed; seed MySQL -> 93 transaksi/30 hari + 30 baris skor; probe render `/admin/dss` 52 KB (ranking VIKOR 5 pemasok, terbaik "Konveksi Bandung Sentosa" = "Solusi Tunggal", 1 produk "Perlu Restock" Rp 850.000), `/staff/dss` 52 KB, landing 35 KB.
- **Batas**: transaksi demo bersifat fiktif (khusus DB pengembangan/laporan), tidak mengubah stok, dan kolom `score` yang lebar hanya dipakai nilai harga - bobot tetap `decimal(8,4)`.

---

## 2026-10-22 - Phase 29: Perbaikan Layout Dashboard (scroll terpotong & menu sidebar mati)
- **Diagnosis 1 (halaman terpotong di atas)**: anchor/hash URL + scroll restoration browser + `.main-content { min-height: 100vh }` di bawah topbar sticky (64px) membuat halaman selalu lebih tinggi dari layar dan judul section tertutup topbar.
- **CSS** `public/css/la-mamora.css`: `.main-content` -> `min-height: calc(100vh - var(--lm-topbar-height))`; `html.lm-dashboard-root { scroll-padding-top }`; `scroll-margin-top` untuk `.dashboard-section` dan `[data-dss-section]`.
- **Layout** `resources/views/layouts/app.blade.php`: `<html class="lm-dashboard-root">` + script reset scroll (`history.scrollRestoration = 'manual'` dan `scrollTo(0, 0)` pada DOMContentLoaded/load/pageshow) supaya dashboard selalu dibuka dari paling atas.
- **Diagnosis 2 (menu sidebar mati di halaman DSS)**: link sidebar staff hanya berisi hash (`href="#dashboard"`) dan handler-nya hanya ada di JS dashboard staff, jadi di halaman DSS klik hanya mengganti hash tanpa navigasi; form logout pun hanya ada di section `#pengaturan` dashboard staff.
- **Sidebar** `resources/views/layouts/sidebar.blade.php`: link staff kini URL penuh `route('staff.dashboard')#<section>` (tetap menyimpan `data-bs-target`); "Pengaturan" staff memakai section in-page di dashboard staff, dan modal Pengaturan (isi logout) di halaman lain; modal Pengaturan kini dirender untuk staff di luar dashboard staff.
- **JS** `resources/views/staff/dashboard.blade.php`: `navLinks` difilter hanya section yang benar-benar ada (link modal Profil tidak lagi kena `preventDefault`), hash dibaca dari `href` penuh, plus `scrollTo(0, 0)` saat pindah section; `admin/dashboard.blade.php` dan kedua halaman DSS ditambahkan `scrollTo(0, 0)`.
- **Test** `tests/Feature/DssSidebarNavigationTest.php` (4 test baru): link sidebar staff/admin, akses Pengaturan + logout, guard scroll reset, dan modal Profil.
- **Verifikasi**: `--filter=DssSidebarNavigationTest` **4/4 lolos, 43 assertions**; `--filter=StaffDashboardTabTest` **5/5 lolos, 96 assertions**; `--filter=Dss` **45/45 lolos, 226 assertions**; `--filter=Dashboard` **7/7 lolos, 119 assertions**.
- **Batas**: belum diverifikasi manual di browser; `pint` dan test suite penuh tidak dijalankan sesuai instruksi.

---

## 2026-10-22 - Phase 30: Alur Approval Restock Kembali ke Menu Persetujuan
- **Diagnosis**: `ManagerController::updateStatus()` memakai `redirect()->back()`. Header `Referer` tidak membawa fragment hash, sehingga admin selalu mendarat di section default dashboard (Dashboard Manager), bukan di menu "Persetujuan Restock", dan pesan sukses jadi tidak terlihat.
- **Controller** `app/Http/Controllers/ManagerController.php`: `updateStatus()` kini `redirect()->to(route('admin.dashboard').'#persetujuan-restock')` dengan flash `success` berisi nama produk, jumlah pcs, dan status (disetujui/ditolak).
- **Perilaku lain tetap**: stok produk hanya bertambah otomatis untuk status `approved`; section tujuan sudah ikut `scrollTo(0, 0)` dari Phase 29 sehingga pesan sukses langsung terlihat di bagian atas halaman.
- **Aksi lain di halaman terkait**: `updateProduct()`/`destroyProduct()` sudah memakai hash eksplisit `#kelola-produk`, `StaffController::storeRestock()` -> `#pengajuan-restock`, `storeProduct()` -> `?produk=<id>#pengajuan-restock`, dan modul DSS -> `#dss-restock`; tidak ada lagi `redirect()->back()` pada alur OAS.
- **Test** `tests/Feature/RestockApprovalRedirectTest.php` (3 test baru): approve dan reject kembali ke `#persetujuan-restock`, stok bertambah hanya saat disetujui, pesan sukses menyebut produk.
- **Verifikasi**: `php artisan test --filter=RestockApprovalRedirectTest` **3/3 lolos, 13 assertions**; test suite penuh dan `pint` tidak dijalankan sesuai instruksi.
- **Batas**: belum diverifikasi manual di browser.

---

## 2026-10-22 - Phase 31: Master Supplier (CRUD + pencarian/filter status)
- **Kebutuhan kolom baru**: migration `2026_10_22_090000_add_supplied_categories_to_suppliers_table.php` menambah kolom `supplied_categories` (string 255, nullable, daftar kategori dipisah koma) ke tabel `suppliers`; migration sudah dijalankan di MySQL.
- **Model** `app/Models/Supplier.php`: `supplied_categories` masuk `$fillable` + helper `suppliedCategoryList()` yang memecah daftar kategori.
- **Controller** `app/Http/Controllers/SupplierMasterController.php`: `index()` (pencarian `q` + filter `status` all/active/inactive + prefill `edit`), `store()` (kode otomatis `SUP-xxx`), `update()`, `destroy()` (khusus admin; diblokir bila pemasok sudah dipakai di skor/penawaran/rekomendasi/pengajuan), `authorizeRoles()`, `validated()`, `generateCode()`, `usageOf()`.
- **Route** `routes/web.php`: grup `role:staff,admin` dengan `supplier.master` (GET `/master-supplier`), `supplier.master.store` (POST), `supplier.master.update` (PUT), `supplier.master.destroy` (DELETE, `role:admin`).
- **View** `resources/views/supplier/master.blade.php`: header, metric row, form cari/filter, form tambah/ubah (kategori bahan, lead time, minimal order, rating, status aktif), tabel daftar + edit + hapus; kategori diambil dari `categories` agar konsisten dengan master produk.
- **Hak akses**: admin penuh (tambah/ubah/hapus), staff tambah+ubah saja (hapus -> 403).
- **Test** `tests/Feature/SupplierMasterTest.php` (11 test baru): akses per role, auto kode `SUP-xxx`, tambah/ubah/hapus, blokir hapus pemasok terpakai, pencarian + filter status, prefill form ubah, dropdown kategori.
- **Verifikasi**: `php artisan test --filter=SupplierMasterTest` **11/11 lolos, 52 assertions**; `pint` dan test suite penuh tidak dijalankan sesuai instruksi.
- **Batas**: belum diverifikasi manual di browser.

---

## 2026-10-22 - Phase 32: Penawaran Supplier di Form Staff (Pengajuan Restock & Master Produk)
- **Kebutuhan**: form staff belum punya info pemasok, sehingga DSS tidak punya data harga beli/lead time/minimal order dari operasional.
- **Controller** `app/Http/Controllers/StaffController.php`: `index()` mengirim `$suppliers` (hanya `is_active = true`); `storeRestock()` dan `storeProduct()` kini menerima `supplier_id`, `supplier_price`, `supplier_lead_time_days`, `supplier_min_order_qty`; helper baru `syncSupplierOffer()` memakai `ProductSupplier::updateOrCreate()`.
- **Validasi**: `supplier_id` memakai `Rule::exists('suppliers','id')->where('is_active', true)`, jadi pemasok nonaktif ditolak walau dipilih manual; tanpa `supplier_id` tidak ada relasi yang dibuat.
- **Data**: `restock_requests.supplier_id` diisi sesuai pemasok yang dipilih; tabel `product_suppliers` (harga beli, lead time, minimal order, `is_preferred = false`) menjadi sumber data DSS.
- **View** `resources/views/staff/dashboard.blade.php`: dropdown "Pilih Supplier" + field Harga Beli/Lead Time/Minimal Order di Form Pengajuan Restock dan di Form Tambah Master Produk, plus link "Tambah di Master Supplier" menuju `/master-supplier`.
- **Test** `tests/Feature/StaffSupplierOfferTest.php` (6 test baru): simpan penawaran ke `product_suppliers`, update-or-create tanpa duplikat, penawaran ikut tersimpan saat tambah produk, tanpa supplier tidak ada relasi, dropdown hanya pemasok aktif, pemasok nonaktif ditolak.
- **Verifikasi**: `php artisan test --filter=StaffSupplierOfferTest` **6/6 lolos, 33 assertions**; regresi `--filter=StaffDashboardTabTest` **5/5**, `--filter=Dss` **45/45**, `--filter=Dashboard` **7/7**, `--filter=Restock` **20/20**, `--filter=Product` **8/8**, `--filter=Supplier` **27/27**.
- **Batas**: belum diverifikasi manual di browser.

---

## 2026-10-22 - Phase 33: Ringkasan Test Master Supplier & Penawaran (Fase C)
- Ringkasan hasil verifikasi Fase C: SupplierMasterTest 11/11, StaffSupplierOfferTest 6/6, StaffDashboardTabTest 5/5, Dss 45/45, Dashboard 7/7, Restock 20/20, Product 8/8, Supplier 27/27.
- Dokumentasikan rangkuman di `todo.md` (Phase 31–33).

---

## 2026-10-22 - Phase 34: UI Pelanggan Selaras dengan Design System La Mamora
- Desain ulang halaman pelanggan agar memakai palet/token La Mamora, responsif mobile, tanpa mengubah route/logika bisnis.
- Tambah `public/css/la-mamora-shop.css` (sidebar/drawer, kartu katalog, filter, detail produk, favorit, pesanan, modal, chatbot, auth `.lm-auth*`, dark mode `.lm-dark`).
- `resources/views/layouts/toko-shell.blade.php`: gabung dengan `la-mamora.css` + `la-mamora-shop.css`, drawer navigasi, dark mode, modal profil/keranjang/bantuan, dan floating chatbot.
- Katalog `resources/views/toko/index.blade.php` extends shell; filter, slider harga, pencarian, modal beli, dan favorit tetap berfungsi.
- Hilangkan inline style pada `resources/views/toko/show.blade.php` dan `resources/views/toko/favorit.blade.php`; `resources/views/toko/pesanan.blade.php` beralih ke layout shell.
- Desain ulang `resources/views/auth/login.blade.php` dan `resources/views/auth/register.blade.php` menggunakan `.lm-auth*` (tanpa Bootstrap CDN).
- Tambah `tests/Feature/CustomerPagesRenderTest.php` (katalog/detail/favorit/pesanan/login/register, marker CSS/UI, drawer, tanpa warna biru Shopee). `php artisan test --filter=CustomerPagesRenderTest --compact` lulus.
- Pastikan token shadow `--lm-shadow-sm/-shadow/-shadow-lg` tersedia di `public/css/la-mamora.css`.

---

## 2026-10-22 - Phase 35: Keranjang Pelanggan (DB-only) & Isolasi Data
- Audit: belum ada tabel/route keranjang (pembelian langsung via `toko.buy`); favorit/riwayat transaksi sudah terikat `auth()->id()`, tidak ada endpoint yang menerima `user_id` dari request; profil memakai `Auth::user()`.
- Tambah tabel `carts` (`user_id` FK->users cascade, `product_id` FK->products, `quantity`, unique(user_id,product_id)) + `app/Models/Cart.php` dengan `User::carts()` dan `Product::cartItems()`.
- `app/Http/Controllers/CartController.php`: index/tambah/ubah/hapus/kosongkan/checkout + niat tamu; route `toko.cart.*` di middleware `auth` + `role:pelanggan`.
- Isolasi: `Cart::resolveRouteBinding()` hanya mencari keranjang milik user login sehingga id milik orang lain 404; `checkout` memproses `$user->carts()` saja; `intent`/`addFromIntent` menolak staff/admin.
- Checkout dalam `DB::transaction` + `lockForUpdate()`: buat `transactions` + `transaction_details`, kurangi stok, hapus keranjang pemilik.
- `app/Services/TransactionCustomerResolver.php` menormalkan `transactions.customer_id` untuk MySQL (`users.id`) dan SQLite (`customers.id`), dipakai checkout, pembelian langsung, dan riwayat pesanan.
- Tombol "Tambah ke Keranjang" di kartu produk, detail, favorit, rekomendasi, dan landing; tamu diarahkan login lalu kembali. Badge jumlah item di sidebar + topbar lewat `CustomerContextComposer`.
- `resources/views/toko/cart.blade.php` mengikuti design system La Mamora; CSS keranjang ditambahkan ke `public/css/la-mamora-shop.css`.
- Verifikasi: `--filter=CartIsolationTest` 16/16 (57), `--filter=CheckoutIsolationTest` 6/6 (24), `--filter=FavoriteIsolationTest` 5/5 (20), `--filter=CustomerPagesRenderTest` 7/7 (77), `--filter=LandingRecommendationTest` 5/5 (19), `--filter=ProductRecommendationServiceTest` 8/8 (22), `--filter=StaffDashboardTabTest` 5/5 (96), `--filter=DssDemoTransactionSeederTest` 3/3 (11), `--filter=DssRoleAccessTest` 4/4 (11).
- Batas: `pint` dan test suite penuh tidak dijalankan; fitur belum diverifikasi manual di browser.

---

## 2026-10-23 - Phase 36: Tema Global (token theme-baru.css, dark mode, kontras)
- `public/css/la-mamora.css`: blok `:root` ditulis ulang memakai token `theme-baru.css` (primary lime `#aff33e` dengan teks hitam, background `#fbfcf8`, accent-foreground `#166534`, chart-1..5, sidebar, logo) tanpa menghapus nama `--lm-*` lama yang kini menjadi alias.
- Jembatan Bootstrap 5.3 diperluas: `--bs-body-bg/-color`, `--bs-border-color`, `--bs-border-radius: 1rem`, `--bs-primary(-rgb)`, `--bs-secondary`, `--bs-link-color`, `--bs-font-sans-serif` -> Inter; palet emphasis/subtle ikut diturunkan dari token.
- Mode gelap: class `.dark`, `.lm-dark`, dan `data-bs-theme="dark"` berbagi satu blok token gelap (`background #020617`, `card #0f172a`, `accent #14532d`); langit-langit Bootstrap gelap juga diarahkan ke token.
- `resources/views/layouts/theme.blade.php` (baru): modul bersama `window.lmTheme` yang memasang `.dark` + `data-bs-theme` di `<html>`, menyimpan pilihan di `localStorage` (`lm-theme`), default terang, dan mengikat semua `[data-theme-toggle]` (dipasang no-flash di `<head>`).
- Tombol ganti tema ditambahkan ke topbar dashboard admin/staff, topbar toko, navbar landing, dan diaktifkan lewat `#theme` lama; font Inter + JetBrains Mono dimuat di landing, toko, login, register, dan dashboard.
- Sidebar admin/staff memakai token `sidebar`, dengan item aktif lime + teks hitam; kartu, tabel, form, modal, dropdown memakai token `card/border/input/muted/ring/popover` + shadow 8px/20px/0.05.
- Logo (`.lm-mark`, `.logo`, `.avatar`) memakai `--lm-logo-ring` / `--lm-logo-letter` agar lime terbaca di terang (lingkaran gelap) dan gelap (lime).
- Grafik Chart.js admin (`chartTren`, `chartTopProduk`) membaca token `--lm-chart-1..5` via `getComputedStyle` dan ikut berubah saat tema diganti; warna Shopee/Bootstrap lama di view dihapus.
- Verifikasi: `--filter=ThemeToggleRenderTest` 3/3 (26), `--filter=CustomerPagesRenderTest` 7/7, `--filter=StaffDashboardTabTest`, `--filter=DssPageRenderTest`, `--filter=LandingRecommendationTest`, `--filter=DssSidebarNavigationTest`, `--filter=SupplierMasterTest` total 43/43 lulus.
- Batas: kontras dihitung manual per token; `pint`/suite penuh dan verifikasi browser tidak dijalankan.

## 2026-10-23 - Phase 37: Logo Resmi La Mamora (inline SVG bertoken)
- `resources/views/layouts/lamamora-logo.blade.php` (baru): komponen inline SVG logo resmi (`public/images/lamamora-logo.svg`) tanpa metadata c2pa; warna memakai `var(--lm-logo-bg/--lm-logo-ring/--lm-logo-letter)` sehingga ikut tema; menerima parameter `height` (default 40) dan `class`.
- `public/css/la-mamora.css`: token logo diarahkan ke token canonical - `--lm-logo-ring: var(--lm-primary)`, `--lm-logo-letter: var(--lm-foreground)`, `--lm-logo-bg: transparent` (terang & gelap); rule `.lm-mark` diganti `.sidebar-brand .lm-logo-svg` (34px).
- `public/css/la-mamora-shop.css`: ukuran `.brand .lm-logo-svg` (36px) dan `.lm-auth-brand .lm-logo-svg` (40px); `.logo` lama dipertahankan sebagai fallback.
- Logo dipasang inline di navbar landing (44px), sidebar dashboard admin/staff (34px), sidebar toko (36px), serta kartu login & register (40px) - menggantikan kotak huruf "L" lama; konsisten di latar terang dan gelap.
- Verifikasi: `--filter=ThemeToggleRenderTest` 4/4 (logo inline + token), `--filter=CustomerPagesRenderTest|StaffDashboardTabTest|DssPageRenderTest|LandingRecommendationTest|DssSidebarNavigationTest|SupplierMasterTest` total 47/47 lulus; `vendor/bin/pint` bersih.

## 2026-10-23 - Phase 38: Perbaikan UI Pelanggan (drawer, filter server, keranjang, pembayaran simulasi)
- **A. Sidebar drawer**: `toko-shell.blade.php` + `la-mamora-shop.css` mengubah sidebar jadi drawer fixed (280px, tombol X, overlay, Esc, klik item, lock scroll) berlaku di semua ukuran layar, menu Profil membuka modal akun.
- **B. Filter katalog server-side**: `TokoController::index` menambah `resolveCatalogFilters()` (q, kategori[], jenis[], ukuran[], warna[], min/max, urut `terbaru|termurah|termahal|terlaris` via `withSum`); view memakai panel lipat + chip filter aktif + Reset, opsi dinamis dari DB. Seeder `CategorySeeder` idempoten (Atasan, Celana, Outer, Bahan kain, Aksesori).
- **C. Perbaikan keranjang**: tombol "+ Keranjang" kini hanya membuka modal konfirmasi (bukan submit langsung), item tersimpan hanya setelah POST ber-CSRF; tambah `CartAddFlowTest` (GET ditolak 405, tamu redirect login, item hanya setelah POST).
- **D. Pembayaran simulasi**: migrasi `payment_methods` + `transaction_payments`, model `PaymentMethod`/`TransactionPayment`, `PaymentService`, seeder metode (Kartu, ATM/VA, E-Money, Direct Debit, Gerai, Tunai). Modal katalog/detail & checkout memakai akordeon metode; server menolak kode tidak aktif, menyimpan kode singkat, membuat VA (prefix per bank) / kode gerai + `expires_at` 24 jam, dan hanya `phone_last4`.
- Keamanan: nomor kartu/CVV/nomor rekening tidak punya atribut `name`, tidak dikirim, tidak disimpan, tidak di-log; simulasi murni tanpa payment gateway.
- Riwayat: nilai `payment_method` lama (E-Wallet, M-Banking, Transfer Bank, Cash) tetap tampil via fallback `PaymentMethod::labelFor()`; kartu "Menunggu Pembayaran" tampil di Riwayat Pesanan.
- Verifikasi: `--filter=CartIsolationTest|CheckoutIsolationTest|CatalogFilterTest|CartAddFlowTest|CheckoutPaymentTest|CustomerPagesRenderTest|FavoriteIsolationTest` total 51/51 lulus. `pint`/suite penuh tidak dijalankan.

---

## 2026-10-23 - Phase 39: Restyle dashboard admin & staff (HeroUI-like)
- **FASE A - UI Kit**: Menambahkan 16 komponen Blade `resources/views/components/ui/` (`button`,`card`,`stat-card`,`badge`,`chip`,`input`,`select`,`textarea`,`tabs`,`table`,`modal`,`dropdown`,`toast`,`pagination`,`empty-state`,`avatar`) dengan API yang konsisten.
- **FASE A - Design layer**: Menambahkan layer `.ui-*` di `public/css/la-mamora.css` (token `--ui-*`, radius/shadow/focus, card/button/badge/alert/modal/dropdown/table sticky/segmented tabs/form/avatar) dengan dukungan dark mode & aksesibilitas.
- **FASE B - Aplikasi**: Terapkan komponen ke `layouts/app.blade.php`, `layouts/sidebar.blade.php`, `admin/dashboard.blade.php`, `staff/dashboard.blade.php`, `admin/dss.blade.php`, `staff/dss.blade.php`, `supplier/master.blade.php` (toast/empty-state/stat-card/table/tabs/chip/avatar) tanpa mengubah ID/kelas yang di-assert tes.
- **Verifikasi**: `StaffDashboardTabTest`, `DssPageRenderTest`, `DssSidebarNavigationTest`, `ThemeToggleRenderTest`, `SupplierMasterTest` lulus. `php artisan view:cache` berhasil.
