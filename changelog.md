# Changelog - Laravel TPS & OAS

## 2026-09-17 - Phase 1: Role Refactoring & Database Update
- Renamed `app/Http/Controllers/KasirController.php` → `app/Http/Controllers/StaffController.php`
- Renamed `resources/views/kasir/` → `resources/views/staff/`
- Updated `routes/web.php`: `/kasir/` → `/staff/`, controller references updated
- Updated `resources/views/staff/dashboard.blade.php`: navbar text, form action route
- Created migration `2026_09_17_145733_update_role_kasir_to_staff.php` - updated user roles from 'kasir' to 'staff' in DB
- Ran `php artisan migrate` successfully

## 2026-09-17 - Phase 2: Single Portal Login
- Updated `resources/views/welcome.blade.php`: removed role-specific navbar buttons, single "Masuk ke Aplikasi" login button
- Updated `app/Http/Controllers/AuthController.php`: role redirection - pelanggan→/toko, staff→/staff/dashboard, admin→/admin/dashboard

## 2026-09-17 - Phase 3: Fitur Transaksi Pembeli (Ditenhapi/Major Enhancement)
- Added route `POST /toko/buy/{product_id}` named `toko.buy`
- Created `app/Models/Transaction.php` - New Eloquent model for transactions table
  - Kolom: `id`, `customer_id` (FK → users), `employee_id` (FK → users, NULLABLE), `date` (datetime), `total` (decimal 10,2), `status` (enum: pending/processing/completed/cancelled)
  - Relasi: `customer()` → User, `employee()` → User
- Updated `app/Http/Controllers/TokoController.php`:
  - Enhanced `buyProduct()` method menerima parameter quantity dari form input (default 1)
  - Validasi stok produk sebelum pembelian
  - Kurangi stok produk: `$product->stock -= $quantity`
  - Simpan record transaksi ke database melalui `Transaction::create()`:
    * `customer_id` = auth()->user()->id
    * `employee_id` = NULL (transaksi mandiri pelanggan)
    * `date` = now()
    * `total` = $product->price × $quantity (menggunakan kolom `total`, bukan `total_payment`)
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
- **Perbaikan FK `customer_id`**: Direpoint dari `customers` table → `users` table via raw SQL `ALTER TABLE`
- **Perbaikan FK `employee_id`**: Direpoint dari `employees` table → `users` table, dibuat **NULLABLE** untuk transaksi pelanggan mandiri
- **Kolom `total`**: Tersedia dengan tipe `decimal(10,2)` dan nilainya diisi `harga × quantity` di controller
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
- Added route `POST /chatbot/message` → `ChatbotController@message` (named `chatbot.message`)
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
- Created `app/Models/Category.php` - model untuk tabel `categories` (`id`, `name`, timestamps) + relasi `products()` → `hasMany(Product::class)`
- Updated `app/Models/Product.php`: ditambahkan relasi `category()` → `belongsTo(Category::class)`
- Updated `app/Http/Controllers/StaffController.php` → `index()`:
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
  - Menu **Dashboard** (role staff) → `data-bs-target="#dashboard"`, href `#dashboard`
  - Menu **Monitoring Stok** → href & target `#monitoring-stok` (sebelumnya `#stok-produk`)
  - Menu **Pengajuan Restock** → href & target `#pengajuan-restock`
  - Menu **Riwayat Restock** → href & target `#riwayat-restock`
  - Menu **Pengaturan** → kondisi per role: staff memakai section `#pengaturan`, role lain tetap membuka modal `#settingsModal`
  - Atribut `data-bs-toggle="tab"` sengaja **dihapus** dari link staff; perpindahan section diurus JS manual agar tidak ada dua handler (Bootstrap data-API + JS)
  - Semua atribut tab hanya di-render untuk role `staff` agar dashboard admin & pelanggan tidak berubah

### Dashboard Staff (5 section)
- `resources/views/staff/dashboard.blade.php` dipecah menjadi 5 section (`<div class="dashboard-section">`) di dalam wrapper `#staffDashboard`; hanya `#dashboard` yang tampil awal, section lain memakai `d-none`
  1. **`#dashboard`** (default aktif) — ringkasan singkat:
     - 3 widget statistik: Total Produk, Stok Kritis, Restock Pending
     - "Ringkasan Stok Produk" (maks 5 produk) dengan kategori, varian, harga, sisa stok
     - "Ringkasan Pengajuan Terbaru" (tabel 5 pengajuan terakhir + badge status)
  2. **`#monitoring-stok`** — tabel detail produk lengkap: `ID | Nama Produk | Kategori | Type | Size | Color | Price | Stock | Status`, plus input **Search/Filter client-side** (`#search-product`, info jumlah hasil di `#search-product-info`) untuk cari nama/ukuran/warna produk dengan baris "Produk tidak ditemukan"
  3. **`#pengajuan-restock`** — atas: Form Pengajuan Restock Barang (OAS); bawah: "Daftar Pengajuan Menunggu Keputusan (Pending Only)" yang **hanya** menampilkan status `pending`
  4. **`#riwayat-restock`** — tabel lengkap seluruh riwayat (Pending/Approved/Rejected) dengan badge `bg-warning text-dark` / `bg-success` / `bg-danger`, kolom ID, Tanggal, Produk + varian, Jumlah, Status, Catatan, serta rekap jumlah per status di header
  5. **`#pengaturan`** — Card "Pengaturan & Keamanan Akun" berisi form POST `route('logout')` + `@csrf` dengan tombol `btn btn-outline-danger` ("Logout Akun"), berdampingan dengan Card "Informasi Akun"
- Badge status stok 3 tingkat: `stock == 0` → **Stok Habis** (danger), `stock <= 5` → **Stok Kritis** (warning), selainnya **Stok Aman** (success)
- JS (`@push('scripts')`) — navigasi section manual, tanpa reload:
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
- `routes/web.php`: tambah route `POST /staff/products` → `StaffController@storeProduct` (named `staff.products.store`), di dalam grup `auth`
- `app/Http/Controllers/StaffController.php`:
  - Import `App\Models\Category`; `index()` kini mengirim `$categories = Category::orderBy('name')->get()` untuk dropdown kategori
  - Method baru `storeProduct()` dengan validasi:
    * `category_id` → `required|exists:categories,id`
    * `name` → `required|string|max:100`
    * `type` → `nullable|string|max:50`
    * `size` → `nullable|string|max:10`
    * `color` → `nullable|string|max:30`
    * `price` → `required|numeric|min:0`
    * `stock` → `nullable|integer|min:0` (default 0)
  - `max:` mengikuti panjang kolom di skema tabel `products`; `type`/`size`/`color` opsional sesuai kolom yang `nullable`
  - Redirect ke `route('staff.dashboard') . '#monitoring-stok'` (bukan `back()`, karena browser membuang fragment dari header `Referer`) + flash message sukses
- `resources/views/staff/dashboard.blade.php` (section `#monitoring-stok`):
  - Tombol `btn btn-primary` "Tambah Produk Baru" di atas tabel, sejajar dengan input search
  - Modal Bootstrap `#modalTambahProduk`: form berisi **Category** (dropdown dari `$categories`), **Nama Produk**, **Type** (text + `<datalist>` Atasan/Bawahan/Aksesoris), **Size**, **Color**, **Harga** (number `step="0.01"`), **Stok Awal** (default 0)
  - Alert error + `old(...)` pada seluruh field + `@error` per field agar input staff tidak hilang saat validasi gagal
  - Modal ditempatkan **di luar** wrapper `#staffDashboard` (wajib: section yang tidak aktif memakai `d-none` → `display:none !important`, yang membuat `.modal` di dalamnya tak terlihat meski sudah `.show`)
  - JS: `data-reopen` + `bootstrap.Modal.getOrCreateInstance(...).show()` membuka kembali modal otomatis setelah redirect validasi gagal
- Dropdown Pengajuan Restock tidak perlu diubah — sudah meng-loop `$products`, dan `storeProduct()` me-redirect sehingga `index()` mengambil ulang data produk terbaru

## 2026-09-30 - Phase 9: Category Fleksibel & Shortcut Produk Baru
- `app/Http/Controllers/StaffController.php` → `storeProduct()`:
  - Validasi `category_id|exists:categories,id` diganti menjadi `category|required|string|max:50` (nama kategori, `max:50` mengikuti kolom `categories.name`)
  - `Category::firstOrCreate(['name' => trim($data['category'])])` — staff boleh memilih dari datalist **atau** mengetik kategori baru; kategori otomatis dibuat bila belum ada, lalu `->id` dipakai untuk `products.category_id`
  - Redirect memakai hidden field `_return` (section asal staff) yang di-whitelist terhadap 5 ID section yang valid, default `monitoring-stok`
- `resources/views/staff/dashboard.blade.php`:
  - Field **Category** pada `#modalTambahProduk` diubah dari `<select>` kaku menjadi `<input type="text" list="category-list">` + `<datalist id="category-list">` berisi kategori yang sudah ada; `@error('category_id')` → `@error('category')`; ditambahkan helper "Kategori yang belum ada akan dibuat otomatis"
  - Hidden input `_return` (`#product-form-return`) pada form modal
  - JS: event `show.bs.modal` mengisi `_return` dengan ID section yang sedang aktif (`document.querySelector('.dashboard-section:not(.d-none)')`), sehingga setelah simpan staff kembali ke tab asalnya — `#pengajuan-restock` bila modal dibuka dari tab itu, `#monitoring-stok` bila dari tab Monitoring
  - Tab `#pengajuan-restock`: tombol kecil `btn-outline-primary` "+ Produk Baru" di sebelah kanan label "Pilih Produk" yang membuka `#modalTambahProduk` (`type="button"` agar tidak ikut submit form restock)
- Catatan teknis: `categories.name` dan `products.name` belum punya unique index, sehingga `firstOrCreate` aman untuk pemakaian normal tetapi tetap race-prone bila dua staff mengetik kategori identik bersamaan

## 2026-09-30 - Phase 10: Tambah Produk Inline + Dropdown Select Bertingkat
Pemuatan produk dipindah dari tab Monitoring Stok ke tab Pengajuan Restock agar alur "daftar barang → buat barang → ajukan restock" berada dalam satu halaman.

- Penataan lokasi (semua di `resources/views/staff/dashboard.blade.php`):
  - Tombol "Tambah Produk Baru" dihapus dari header Monitoring Stok (kolom search dilebarkan ke `col-lg-5`)
  - Tombol "+ Produk Baru" dihapus dari header label "Pilih Produk" di Form Pengajuan Restock
  - Seluruh modal `#modalTambahProduk` (beserta hidden `_form`, `_return`, dan atribut `data-reopen`) dihapus
  - Card baru **"Tambah Master Produk Baru"** dipasang di bawah Form + Tabel Pengajuan Restock, berisi form inline `POST staff/products.store` dengan blok `@if($errors->any())` dan seluruh input produk
- UI Kategori & Type — dropdown select bertingkat (menggantikan `<datalist>` bawaan browser):
  - `<select id="category-select">` memuat kategori yang sudah ada plus opsi teratas `+ Tambah Kategori Baru...`; `<select id="type-select">` memuat Atasan/Bawahan/Aksesoris plus `+ Tambah Type Baru...`
  - `<select>` sengaja **tanpa** atribut `name` dan hanya berfungsi sebagai kendali UI; nilainya selalu dibawa oleh `<input name="category">` / `<input name="type">` di bawahnya, sehingga kontrak backend tetap berupa satu string nama
  - Memilih opsi `__baru__` memunculkan (`d-none` dilepas) input teks kecil di bawah dropdown; memilih kategori/type yang ada menyembunyikannya dan menyalin nilai terpilih ke input
  - State awal setelah validasi gagal dihitung di Blade (`$isNewCategory` / `$isNewType`): bila `old()` tidak ada di daftar, select disetel ke `+ Tambah ...` dan input teks ditampilkan
  - Implementasi JS tetap vanilla (`setupSelectBertingkat`) — tanpa jQuery/Select2 agar konsisten dengan gaya halaman ini
- Auto-select produk baru — `app/Http/Controllers/StaffController.php`:
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
- `app/Http/Controllers/StaffController.php` → `storeProduct()`:
  - Aturan validasi `'stock' => 'nullable|integer|min:0'` dihapus
  - `Product::create()` memakai `'stock' => 0` secara hardcoded, sehingga nilai `stock` pada request diabaikan sepenuhnya walau tetap dikirim
- `tests/Feature/StaffDashboardTabTest.php` (belum dijalankan): `stock` sengaja dikirim `5` pada payload untuk membuktikan backend mengabaikannya (`assertSame(0, $product->stock)`), plus assert `name="stock"` dan `Stok Awal` tidak lagi muncul di HTML
- Catatan: kolom `products.stock` bermigrasi sebagai `$table->integer('stock')->default(0)` (NOT NULL), jadi nilai hardcoded `0` konsisten dengan skema

## 2026-09-30 - Phase 12: Redirect Pengajuan Restock & "Riwayat Stok" Audit Trail
Tab "Riwayat Restock" diubah menjadi "Riwayat Stok", dan isinya digabung menjadi satu audit trail yang memuat dua jenis aktivitas stok.

- `app/Http/Controllers/StaffController.php`:
  - `storeRestock()`: `redirect()->back()` diganti redirect eksplisit `->to(route('staff.dashboard') . '#pengajuan-restock')`. Sebelumnya fragment hash ikut hilang saat `back()`, sehingga staff selalu mendarat di tab Dashboard.
  - `index()`: menambah variabel `$riwayatStok` — `Collection` hasil `merge()` antara aktivitas **Pengajuan Restock** (dari `$allRestocks`) dan **Produk Baru** (dari `$products`), diurutkan `->sortByDesc('tanggal')->values()`. Varian baru ikut ditambahkan ke `compact()`.
  - Detail baris produk baru memakai nilai stok sebenarnya: `Master Data Terdaftar (N Pcs)`, bukan angka 0 hardcode, agar produk lama yang sudah punya stok tidak tampil salah. Produk baru sendiri selalu 0 sejak Phase 11.
- `resources/views/layouts/sidebar.blade.php`:
  - Link staff: `href`/`data-bs-target` `#riwayat-restock` → `#riwayat-stok`, teks "Riwayat Restock" → "Riwayat Stok".
  - Link admin: `href` `admin.dashboard#riwayat-restock` → `admin.dashboard#riwayat-stok`, teks diubah sama.
- `resources/views/staff/dashboard.blade.php` — section `#riwayat-restock` → `#riwayat-stok`, judul card → "Riwayat Aktivitas Stok", tabel 6 kolom diganti menjadi `Tanggal | Tipe Aktivitas | Produk | Jumlah | Status | Keterangan`:
  - Tipe Aktivitas: badge `Produk Baru` (info) atau `Pengajuan Restock` (secondary).
  - Jumlah: `10 pcs` untuk restock, `—` untuk produk baru.
  - Status: `Terdaftar` (info) untuk produk baru, Pending/Approved/Rejected untuk restock.
  - Badge ringkasan di header dipertahankan, ditambah `Produk Baru: N`.
- `tests/Feature/StaffDashboardTabTest.php` diperbarui (belum dijalankan): 6 referensi `riwayat-restock` → `riwayat-stok`, judul card, assert badge `Produk Baru: 2` + `Master Data Terdaftar (N Pcs)`, assert jumlah baris per tipe (2 produk + 3 restock), dan assert link sidebar admin.

## 2026-09-30 - Phase 13: Perbaikan Fatal `format() on null`
Error "Call to a member function format() on null" muncul di tabel Riwayat Stok saat sebagian record tidak memiliki `created_at` (data lama/import).

- `resources/views/staff/dashboard.blade.php`:
  - Tabel Riwayat Stok: `{{ $row['tanggal']->format(...) }}` → `{{ $row['tanggal']?->format('d/m/Y H:i') ?? '-' }}` (null-safe operator + fallback `"-"`)
  - "Ringkasan Pengajuan Terbaru" (baris ~131) punya pola yang sama dan ikut diperbaiki
- `resources/views/admin/dashboard.blade.php` — tabel persetujuan punya pola identik, ikut diperbaiki Thoughiefungsi di luar halaman staff
- `app/Http/Controllers/StaffController.php` → `index()`:
  - Fallback tanggal: `'tanggal' => $req->created_at ?? $req->updated_at` (dan sama untuk produk) agar tetap ada bila `created_at` null
  - `sortByDesc('tanggal')` diganti `sortByDesc(fn ($row) => $row['tanggal']?->getTimestamp() ?? 0)` — mengurutkan key numerik, record tanpa timestamp otomatis mendarat di akhir daftar

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
- JS `openProfile()` / `closeProfile()`; ditutup lewat tombol ✕, klik backdrop, atau Escape. Handler `keydown` yang sebelumnya hanya menutup `#buyModal` sekarang menutup kedua modal sekaligus.
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
- **Keranjang / Favorit / Bantuan** -> `href="#"` + atribut `data-info` yang membuka modal info baru `#infoModal` berisi judul, ikon, dan penjelasan jujur bahwa fiturnya masih dalam pengembangan (bukan link mati yang diam-diam tidak melakukan apa-apa). Modal ditutup lewat tombol ✕, tombol "Mengerti", klik backdrop, atau Escape (Escape sekarang menutup buy modal, info modal, dan modal profil sekaligus). Definisi teks & ikon disimpan di object `infoContent` di script supaya tidak ada duplikasi HTML.
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

