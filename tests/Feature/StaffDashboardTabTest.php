<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_renders_all_tab_sections(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $category = \DB::table('categories')->insertGetId(['name' => 'Kaos', 'created_at' => now(), 'updated_at' => now()]);
        $product = Product::create([
            'category_id' => $category,
            'name' => 'Kaos Polos',
            'type' => 'Kaos',
            'size' => 'M',
            'color' => 'Merah',
            'price' => 50000,
            'stock' => 3,
        ]);
        $safe = Product::create([
            'category_id' => $category,
            'name' => 'Celana Jeans',
            'type' => 'Celana',
            'size' => 'L',
            'color' => 'Biru',
            'price' => 120000,
            'stock' => 20,
        ]);

        $employeeId = \DB::table('employees')->insertGetId([
            'user_id' => $staff->id,
            'name' => $staff->name,
            'position' => 'Kasir',
        ]);

        RestockRequest::create(['product_id' => $product->id, 'employee_id' => $employeeId, 'jumlah_restock' => 10, 'status' => 'pending']);
        RestockRequest::create(['product_id' => $safe->id, 'employee_id' => $employeeId, 'jumlah_restock' => 5, 'status' => 'approved']);
        RestockRequest::create(['product_id' => $safe->id, 'employee_id' => $employeeId, 'jumlah_restock' => 3, 'status' => 'rejected']);

        $response = $this->actingAs($staff)->get('/staff/dashboard');

        $response->assertOk();

        $html = $response->getContent();

        foreach ([
            'data-bs-target="#dashboard"',
            'data-bs-target="#monitoring-stok"',
            'data-bs-target="#pengajuan-restock"',
            'data-bs-target="#riwayat-stok"',
            'data-bs-target="#pengaturan"',
            'id="dashboard"',
            'id="monitoring-stok"',
            'id="pengajuan-restock"',
            'id="riwayat-stok"',
            'id="pengaturan"',
            'Total Produk',
            'Stok Kritis',
            'Restock Pending',
            'Ringkasan Stok Produk',
            'Ringkasan Pengajuan Terbaru',
            'Daftar Pengajuan Menunggu Keputusan (Pending Only)',
            'Riwayat Aktivitas Stok',
            '<th>Tipe Aktivitas</th>',
            'Master Data Terdaftar',
            'Pengaturan & Keamanan Akun',
            'Logout Akun',
            'id="search-product"',
            '<th>Kategori</th>',
            '<th>Color</th>',
            'Stok Kritis',
            'Stok Aman',
            'Tambah Master Produk Baru',
            'id="category-select"',
            'id="category-input"',
            'id="type-select"',
            'id="type-input"',
            '+ Tambah Kategori Baru...',
            '+ Tambah Type Baru...',
            'Atasan',
            'Bawahan',
            'Aksesoris',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "Missing: {$needle}");
        }

        // Form tambah produk kini inline di section Pengajuan Restock, modal lama sudah dihapus
        $this->assertStringContainsString('action="'.route('staff.products.store').'"', $html);
        $this->assertStringNotContainsString('modalTambahProduk', $html);
        $this->assertStringNotContainsString('data-reopen', $html);
        $this->assertStringNotContainsString('Tambah Produk Baru', $html);
        $this->assertStringNotContainsString('bootstrap.Modal.getOrCreateInstance', $html);
        $this->assertStringNotContainsString('name="_return"', $html);
        $this->assertStringNotContainsString('name="stock"', $html);
        $this->assertStringNotContainsString('Stok Awal', $html);
        $this->assertStringContainsString('Produk baru akan didaftarkan dengan stok 0', $html);

        // Logout hanya boleh muncul di tab Pengaturan, tidak lagi di bawah sidebar
        $this->assertStringNotContainsString('sidebar-footer', $html);
        $this->assertSame(1, substr_count($html, 'action="'.route('logout').'"'));
        $this->assertStringContainsString('class="btn btn-outline-danger"', $html);

        // 2 produk di tabel monitoring stok
        $this->assertSame(2, substr_count($html, '<tr data-product-row '));
        $this->assertStringContainsString('Kaos Polos', $html);
        $this->assertStringContainsString('data-search="kaos polos kaos kaos m merah"', $html);

        // Tabel pending-only hanya 1 baris (hanya status pending)
        $pendingSection = substr($html, strpos($html, 'id="pengajuan-restock"'), strpos($html, 'id="riwayat-stok"') - strpos($html, 'id="pengajuan-restock"'));
        $this->assertSame(1, substr_count($pendingSection, '<span class="badge bg-warning text-dark">Pending</span>'));
        $this->assertStringNotContainsString('Approved', $pendingSection);
        $this->assertStringNotContainsString('Rejected', $pendingSection);

        // Tabel riwayat memuat 3 status
        $riwayatSection = substr($html, strpos($html, 'id="riwayat-stok"'));
        $this->assertStringContainsString('Pending: 1', $riwayatSection);
        $this->assertStringContainsString('Approved: 1', $riwayatSection);
        $this->assertStringContainsString('Rejected: 1', $riwayatSection);

        // Audit trail "Riwayat Stok" menggabungkan produk baru + pengajuan restock
        $this->assertStringContainsString('Produk Baru: 2', $riwayatSection);
        $this->assertStringContainsString('Master Data Terdaftar (3 Pcs)', $riwayatSection);
        $this->assertStringContainsString('Master Data Terdaftar (20 Pcs)', $riwayatSection);
        // 2 produk + 3 pengajuan restock = 5 baris aktivitas
        $this->assertSame(2, substr_count($riwayatSection, '>Produk Baru</span>'));
        $this->assertSame(3, substr_count($riwayatSection, '>Pengajuan Restock</span>'));
        $this->assertStringNotContainsString('<th>Catatan</th>', $riwayatSection);

        // Navigasi section: 5 section, hanya #dashboard yang tampil awal
        $this->assertSame(5, substr_count($html, 'class="dashboard-section'));
        $this->assertStringContainsString('class="dashboard-section" id="dashboard"', $html);
        $this->assertSame(4, substr_count($html, 'class="dashboard-section d-none"'));
        $this->assertStringNotContainsString('data-bs-toggle="tab"', $html);
        $this->assertStringNotContainsString('bootstrap.Tab', $html);
    }

    public function test_tambah_produk_membuat_kategori_baru_dan_produk_otomatis_terpilih(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->post(route('staff.products.store'), [
            'category' => 'Outerwear',
            'name' => 'Jaket Bomber',
            'type' => 'Atasan',
            'size' => 'L',
            'color' => 'Hitam',
            'price' => 250000,
            'stock' => 5,
        ]);

        $product = Product::where('name', 'Jaket Bomber')->firstOrFail();
        $category = \DB::table('categories')->where('name', 'Outerwear')->first();

        $this->assertNotNull($category, 'Kategori baru harus otomatis dibuat oleh Category::firstOrCreate().');
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame('Atasan', $product->type);

        // 'stock' sengaja dikirim 5 untuk membuktikan backend mengabaikannya (field ini sudah dihapus dari form)
        $this->assertSame(0, $product->stock, 'Produk baru harus selalu disimpan dengan stok 0.');

        // Kembali ke section Pengajuan Restock dengan penanda produk baru
        $response->assertRedirect(route('staff.dashboard').'?produk='.$product->id.'#pengajuan-restock');

        // Dropdown "Pilih Produk" harus otomatis menandai produk baru sebagai terpilih
        $html = $this->actingAs($staff)
            ->get(route('staff.dashboard').'?produk='.$product->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<option value="'.$product->id.'" selected="selected">', $html);
        $this->assertStringContainsString('Jaket Bomber (Stok Saat Ini: 0)', $html);
    }

    public function test_kategori_lama_tidak_tergandakan_saat_menambah_produk(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $categoryId = \DB::table('categories')->insertGetId(['name' => 'Kaos', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($staff)->post(route('staff.products.store'), [
            'category' => 'Kaos',
            'name' => 'Kaos Polos Navy',
            'price' => 75000,
        ])->assertRedirect();

        $this->assertSame(1, \DB::table('categories')->where('name', 'Kaos')->count());
        $this->assertSame($categoryId, Product::where('name', 'Kaos Polos Navy')->value('category_id'));
    }

    public function test_riwayat_stok_tetap_render_untuk_record_tanpa_timestamp(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        // Record produk & pengajuan yang sama sekali tidak punya created_at/updated_at
        $categoryId = \DB::table('categories')->insertGetId(['name' => 'Kaos', 'created_at' => now(), 'updated_at' => now()]);
        $productId = \DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'name' => 'Produk Tanpa Tanggal', 'price' => 10000, 'stock' => 0,
            'created_at' => null, 'updated_at' => null,
        ]);
        $employeeId = \DB::table('employees')->insertGetId(['user_id' => $staff->id, 'name' => $staff->name, 'position' => 'Kasir']);
        \DB::table('restock_requests')->insert([
            'product_id' => $productId, 'employee_id' => $employeeId, 'jumlah_restock' => 4,
            'status' => 'pending', 'created_at' => null, 'updated_at' => null,
        ]);

        // Halaman tidak boleh fatal karena format() dipanggil pada null
        $html = $this->actingAs($staff)->get('/staff/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Produk Tanpa Tanggal', $html);
        $this->assertStringContainsString('Master Data Terdaftar (0 Pcs)', $html);
        $this->assertStringContainsString('>—<', $html);
    }

    public function test_sidebar_tetap_memakai_modal_pengaturan_untuk_role_lain(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $html = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-bs-target="#settingsModal"', $html);
        $this->assertStringContainsString('href="'.route('admin.dashboard').'#persetujuan-restock"', $html);
        // Menu admin sudah diubah namanya menjadi "Riwayat Stok"
        $this->assertStringContainsString('href="'.route('admin.dashboard').'#riwayat-stok"', $html);
        $this->assertStringContainsString('>Riwayat Stok</span>', $html);
        $this->assertStringNotContainsString('#riwayat-restock"', $html);
        $this->assertStringNotContainsString('data-bs-toggle="tab"', $html);
        $this->assertStringNotContainsString('sidebar-footer', $html);

        $pelanggan = User::factory()->create(['role' => 'pelanggan']);
        $toko = $this->actingAs($pelanggan)->get('/toko')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-bs-toggle="tab"', $toko);
        $this->assertStringNotContainsString('sidebar-footer', $toko);
    }
}
