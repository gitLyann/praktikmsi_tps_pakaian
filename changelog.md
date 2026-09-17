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
- **Perbaikan FK `customer_id`**:Direpoint dari `customers` table → `users` table via raw SQL `ALTER TABLE`
- **Perbaikan FK `employee_id`**:Direpoint dari `employees` table → `users` table, dibuat **NULLABLE** untuk transaksi pelanggan mandiri
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