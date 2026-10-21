<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestockRequest;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Support\HandlesProductImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerController extends Controller
{
    use HandlesProductImage;

    // Menampilkan daftar pengajuan restock untuk disetujui + data MIS Analytics
    public function index(Request $request)
    {
        $restockRequests = RestockRequest::with(['product', 'employee'])->latest()->get();

        // Master produk ikut dimuat karena Manager boleh mengubah dan menghapus produk.
        $products = Product::with('category')->orderBy('id')->get();
        $categories = Category::orderBy('name')->get();

        // ===== MIS: periode laporan (filter tanggal, default 6 bulan terakhir) =====
        $dari = $request->query('dari');
        $sampai = $request->query('sampai');
        $dariValid = $dari && Carbon::hasFormat($dari, 'Y-m-d');
        $sampaiValid = $sampai && Carbon::hasFormat($sampai, 'Y-m-d');

        $tanggalDari = $dariValid ? Carbon::parse($dari)->startOfDay() : now()->subMonths(5)->startOfMonth();
        $tanggalSampai = $sampaiValid ? Carbon::parse($sampai)->endOfDay() : now()->endOfDay();

        // Bila rentang terbalik, tukar supaya query tetap masuk akal.
        if ($tanggalDari->gt($tanggalSampai)) {
            [$tanggalDari, $tanggalSampai] = [$tanggalSampai, $tanggalDari];
        }

        $periode = [
            'dari' => $tanggalDari->format('Y-m-d'),
            'sampai' => $tanggalSampai->format('Y-m-d'),
            'dari_label' => $tanggalDari->format('d/m/Y'),
            'sampai_label' => $tanggalSampai->format('d/m/Y'),
            'dari_input' => $dariValid ? $dari : '',
            'sampai_input' => $sampaiValid ? $sampai : '',
        ];

        // ===== MIS: Executive Summary KPI =====
        // Kolom nilai transaksi bernama `total` (bukan total_price).
        $omset = (float) Transaction::whereBetween('date', [$tanggalDari, $tanggalSampai])
            ->selectRaw('COALESCE(SUM(total), 0) as omset')
            ->value('omset');

        // Total produk terjual = SUM(quantity) dari transaction_details pada periode yang sama.
        $produkTerjual = (int) TransactionDetail::whereIn(
            'transaction_id',
            Transaction::whereBetween('date', [$tanggalDari, $tanggalSampai])->select('id')
        )->selectRaw('COALESCE(SUM(quantity), 0) as terjual')->value('terjual');

        // Exception reporting: stok kritis = kurang dari 5 pcs.
        $stokKritis = Product::where('stock', '<', 5)->count();
        $restockPending = RestockRequest::where('status', 'pending')->count();

        $kpi = [
            'omset' => $omset,
            'produk_terjual' => $produkTerjual,
            'stok_kritis' => $stokKritis,
            'restock_pending' => $restockPending,
        ];

        // ===== MIS: Tren penjualan bulanan (di-group di PHP agar tidak bergantung
        // pada fungsi DATE_FORMAT/strftime yang berbeda antar database) =====
        $transaksiPeriode = Transaction::whereBetween('date', [$tanggalDari, $tanggalSampai])
            ->orderBy('date')
            ->get(['id', 'date', 'total']);

        $omsetPerBulan = [];
        $terjualPerBulan = [];
        foreach ($transaksiPeriode as $trx) {
            $kunci = $trx->date->format('Y-m');
            $omsetPerBulan[$kunci] = ($omsetPerBulan[$kunci] ?? 0) + (float) $trx->total;
        }

        $detailPeriode = TransactionDetail::whereIn(
            'transaction_id',
            $transaksiPeriode->pluck('id')
        )->get(['transaction_id', 'quantity']);
        $trxDateById = $transaksiPeriode->keyBy('id');
        foreach ($detailPeriode as $detail) {
            $trx = $trxDateById->get($detail->transaction_id);
            if ($trx) {
                $kunci = $trx->date->format('Y-m');
                $terjualPerBulan[$kunci] = ($terjualPerBulan[$kunci] ?? 0) + (int) $detail->quantity;
            }
        }

        // Isi bulan kosong dengan 0 supaya sumbu X berurutan.
        $trenLabels = [];
        $trenOmset = [];
        $trenTerjual = [];
        $kursor = $tanggalDari->copy()->startOfMonth();
        $akhir = $tanggalSampai->copy()->startOfMonth();
        while ($kursor->lte($akhir) && count($trenLabels) < 36) {
            $kunci = $kursor->format('Y-m');
            $trenLabels[] = $kursor->format('m/Y');
            $trenOmset[] = round($omsetPerBulan[$kunci] ?? 0);
            $trenTerjual[] = $terjualPerBulan[$kunci] ?? 0;
            $kursor->addMonth();
        }

        // ===== MIS: Top 5 produk terlaris (best seller) =====
        $topProduk = TransactionDetail::whereIn(
            'transaction_id',
            Transaction::whereBetween('date', [$tanggalDari, $tanggalSampai])->select('id')
        )
            ->selectRaw('product_id, SUM(quantity) as terjual, SUM(subtotal) as omset')
            ->groupBy('product_id')
            ->orderByDesc('terjual')
            ->limit(5)
            ->get();

        $namaProdukTop = Product::whereIn('id', $topProduk->pluck('product_id'))
            ->get(['id', 'name'])
            ->keyBy('id');
        $topProduk = $topProduk->map(function ($row) use ($namaProdukTop) {
            $row->nama = $namaProdukTop->get($row->product_id)->name ?? 'Produk #' . $row->product_id;
            return $row;
        });

        // ===== MIS: Exception reporting (produk stok < 5 pcs) =====
        $lowStock = Product::with('category')
            ->where('stock', '<', 5)
            ->orderBy('stock')
            ->get();

        // ===== MIS: Audit trail pengajuan restock (nama pengaju + status approval) =====
        $auditTrail = RestockRequest::with(['product', 'employee'])
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.dashboard', compact(
            'restockRequests',
            'products',
            'categories',
            'periode',
            'kpi',
            'trenLabels',
            'trenOmset',
            'trenTerjual',
            'topProduk',
            'lowStock',
            'auditTrail'
        ));
    }

    // Update status pengajuan (Approve / Reject) & update stok otomatis
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $restock = RestockRequest::findOrFail($id);
        $restock->status = $request->status;
        $restock->save();

        // Jika disetujui, otomatis tambahkan stok di tabel products
        if ($request->status === 'approved') {
            $product = Product::findOrFail($restock->product_id);
            $product->stock += $restock->jumlah_restock;
            $product->save();
        }

        $namaProduk = $restock->product?->name ?? 'Produk';
        $pesan = $request->status === 'approved'
            ? 'Pengajuan restock '.$namaProduk.' ('.$restock->jumlah_restock.' pcs) disetujui, stok produk bertambah.'
            : 'Pengajuan restock '.$namaProduk.' ('.$restock->jumlah_restock.' pcs) ditolak.';

        // redirect()->back() membuang fragment hash, sehingga admin selalu mendarat
        // di dashboard utama. Arahkan eksplisit ke menu Persetujuan Restock
        // supaya approve/reject tidak membuat admin kehilangan posisi halaman.
        return redirect()
            ->to(route('admin.dashboard') . '#persetujuan-restock')
            ->with('success', $pesan);
    }

    // Catatan: tidak ada storeProduct() di sini. Penambahan master produk
    // hanya dilakukan lewat dashboard Staff (StaffController::storeProduct),
    // supaya Manager fokus pada RUD + Approval OAS.

    // Manager mengubah master produk yang sudah ada (KMS Produk)
    public function updateProduct(Request $request, Product $product)
    {
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
            ->to(route('admin.dashboard') . '#kelola-produk')
            ->with('success', 'Produk berhasil diperbarui: ' . $product->name . '.');
    }

    // Manager menghapus produk. Produk yang sudah pernah terjual tidak boleh dihapus
    // karena transaction_details memakai foreign key tanpa cascade, dan riwayat
    // penjualan tidak boleh ikut terhapus.
    public function destroyProduct(Product $product)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $sudahTerjual = TransactionDetail::where('product_id', $product->id)->exists();

        if ($sudahTerjual) {
            return redirect()
                ->to(route('admin.dashboard') . '#kelola-produk')
                ->with('error', 'Produk "' . $product->name . '" sudah pernah terjual '
                    . 'sehingga tidak bisa dihapus agar riwayat penjualan tetap utuh.');
        }

        // Baris restock_requests terhapus otomatis lewat cascade di foreign key.
        // Baris favorites juga terhapus otomatis lewat cascade.
        $image = $product->image;
        $namaProduk = $product->name;
        $product->delete();

        $this->deleteProductImage($image);

        return redirect()
            ->to(route('admin.dashboard') . '#kelola-produk')
            ->with('success', 'Produk berhasil dihapus: ' . $namaProduk . '.');
    }
}
