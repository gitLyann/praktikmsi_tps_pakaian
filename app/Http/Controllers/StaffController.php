<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Support\HandlesProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    use HandlesProductImage;
    // Menampilkan halaman dashboard staff beserta data produk dan riwayat restock
    public function index()
    {
        $criticalThreshold = 5;

        $products = Product::with('category')->orderBy('id')->get();
        $topProducts = $products->take(5);
        $categories = Category::orderBy('name')->get();

        $totalProduk = $products->count();
        $stokKritis = $products->where('stock', '<=', $criticalThreshold)->count();
        $restockPending = RestockRequest::where('status', 'pending')->count();

        $recentRestocks = RestockRequest::with(['product', 'employee'])
            ->latest()
            ->take(5)
            ->get();

        $pendingRestocks = RestockRequest::with(['product', 'employee'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $allRestocks = RestockRequest::with(['product', 'employee'])
            ->latest()
            ->get();

        $restockRequests = $allRestocks;

        // Audit trail "Riwayat Stok": gabungkan dua jenis aktivitas stok di satu daftar,
        // yaitu pendaftaran master produk baru dan pengajuan restock, lalu urutkan dari terbaru.
        $riwayatStok = collect()
            ->merge($allRestocks->map(fn ($req) => [
                'tanggal' => $req->created_at ?? $req->updated_at,
                'tipe' => 'Pengajuan Restock',
                'produk' => $req->product->name ?? 'Produk N/A',
                'jumlah' => $req->jumlah_restock,
                'status' => $req->status,
                'keterangan' => $req->catatan ?? '-',
            ]))
            ->merge($products->map(fn ($product) => [
                'tanggal' => $product->created_at ?? $product->updated_at,
                'tipe' => 'Produk Baru',
                'produk' => $product->name,
                'jumlah' => null,
                'status' => 'terdaftar',
                'keterangan' => 'Master Data Terdaftar (' . $product->stock . ' Pcs)',
            ]))
            // Record tanpa timestamp sama sekali diletakkan di akhir daftar
            ->sortByDesc(fn ($row) => $row['tanggal']?->getTimestamp() ?? 0)
            ->values();

        return view('staff.dashboard', compact(
            'products',
            'topProducts',
            'categories',
            'restockRequests',
            'recentRestocks',
            'pendingRestocks',
            'allRestocks',
            'riwayatStok',
            'totalProduk',
            'stokKritis',
            'restockPending',
            'criticalThreshold'
        ));
    }

    // Menyimpan produk baru ke tabel products (Monitoring Stok - Staff)
    public function storeProduct(Request $request)
    {
        // Hanya Staff (dan Admin) yang boleh menambah produk.
        abort_unless(in_array(Auth::user()->role, ['staff', 'admin'], true), 403);

        // Stok sengaja tidak diambil dari request: produk baru selalu didaftarkan dengan stok 0.
        // Pengisian stok fisik dilakukan lewat pengajuan restock setelah produk tersimpan.
        $data = $request->validate([
            'category' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:30',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
        ]);

        // Foto produk disimpan ke public/images/produk supaya bisa langsung diakses tanpa storage:link
        $data['image'] = $this->storeProductImage($request->file('image'));

        // Kategori boleh dipilih dari datalist atau diketik bebas,
        // sehingga otomatis dibuatkan apabila belum ada di database
        $category = Category::firstOrCreate(['name' => trim($data['category'])]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'size' => $data['size'] ?? null,
            'color' => $data['color'] ?? null,
            'image' => $data['image'] ?? null,
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'stock' => 0,
        ]);

        // Form Tambah Master Produk Baru berada di section Pengajuan Restock, jadi kembalikan ke sana
        // dengan ?produk=<id> agar produk baru otomatis terpilih di dropdown "Pilih Produk".
        return redirect()
            ->to(route('staff.dashboard') . '?produk=' . $product->id . '#pengajuan-restock')
            ->with('success', 'Produk baru berhasil ditambahkan: ' . $product->name
            . '. Stok awal 0, silakan ajukan restock untuk mengisi stok fisik.');
    }

    // Memperbarui master produk yang sudah ada (Monitoring Stok - Staff)
    public function updateProduct(Request $request, Product $product)
    {
        // Saat ini belum ada middleware role, jadi pengecekan dilakukan langsung di controller.
        abort_unless(in_array(Auth::user()->role, ['staff', 'admin'], true), 403);

        $data = $request->validate([
            'category' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|max:50',
            'size' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'price' => 'required|numeric|min:0',
        ]);

        // Stok tidak bisa diubah dari form ini: perubahan stok hanya lewat pengajuan restock.
        $category = Category::firstOrCreate(['name' => trim($data['category'])]);

        $product->update([
            'category_id' => $category->id,
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'size' => $data['size'] ?? null,
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $this->syncProductImage(
                $request->file('image'),
                $product->image,
                $request->boolean('remove_image')
            ),
            'price' => $data['price'],
        ]);

        return redirect()
            ->to(route('staff.dashboard') . '#monitoring-stok')
            ->with('success', 'Produk berhasil diperbarui: ' . $product->name . '.');
    }

    // Memproses pengajuan restock dari formulir web (OAS Function - Staff)
    public function storeRestock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'jumlah_restock' => 'required|integer|min:1',
        ]);

        // Mencari employee_id dari user yang sedang login
        // Jika belum ada relasi employee, kita set default ID 1 untuk simulasi
        $employeeId = Auth::user()->employee->id ?? 1;

        RestockRequest::create([
            'product_id' => $request->product_id,
            'employee_id' => $employeeId,
            'jumlah_restock' => $request->input('jumlah_restock'),
            'status' => 'pending',
        ]);

        // redirect()->back() mengembalikan URL tanpa fragment hash, sehingga staff selalu mendarat
        // di tab Dashboard. Arahkan eksplisit agar halaman tetap fokus di tab Pengajuan Restock.
        return redirect()
            ->to(route('staff.dashboard') . '#pengajuan-restock')
            ->with('success', 'Pengajuan restock barang berhasil dikirim ke Manager!');
    }
}
