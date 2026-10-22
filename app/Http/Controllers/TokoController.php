<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\TransactionCustomerResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TokoController extends Controller
{
    public function __construct(private readonly TransactionCustomerResolver $customerResolver)
    {
    }

    // Menampilkan daftar produk dari database ke katalog pembeli
    public function index()
    {
        $products = Product::with('category')->latest()->get();

        // Dipakai untuk checkbox filter kategori di sidebar katalog
        $categories = Category::orderBy('name')->pluck('name');

        // Daftar id produk yang sudah difavoritkan pengguna, supaya tombol hati
        // di katalog langsung tampil dalam keadaan aktif tanpa request tambahan.
        $favoritIds = [];

        if (Auth::check()) {
            $favoritIds = Favorite::where('user_id', Auth::id())
                ->pluck('product_id')
                ->all();
        }

        return view('toko.index', compact('products', 'categories', 'favoritIds'));
    }

    // Halaman "Pesanan Saya": riwayat transaksi milik pengguna yang sedang login
    public function pesanan()
    {
        // Transaksi hanya bisa dibaca berdasarkan user yang sedang login.
        // Id yang dipakai menyesuaikan skema FK `transactions.customer_id`.
        $customerIds = $this->customerResolver->customerIdsFor(Auth::user());

        $transactions = Transaction::whereIn('customer_id', $customerIds)
            ->latest('date')
            ->get();

        return view('toko.pesanan', compact('transactions'));
    }

    // Detail produk: foto, deskripsi, harga, dan status favorit pengguna
    public function show(Product $product)
    {
        $product->load('category');

        $sudahFavorit = $this->apakahFavorit($product);

        return view('toko.show', compact('product', 'sudahFavorit'));
    }

    // Menambah atau menghapus produk dari daftar favorit pengguna
    public function toggleFavorite(Request $request, Product $product)
    {
        $userId = Auth::id();

        $favorit = Favorite::where('user_id', $userId)
            ->where('product_id', $product->id)
            ->first();

        if ($favorit) {
            $favorit->delete();
            $terfavorit = false;
            $pesan = 'Produk dihapus dari favorit.';
        } else {
            Favorite::create([
                'user_id' => $userId,
                'product_id' => $product->id,
            ]);
            $terfavorit = true;
            $pesan = 'Produk ditambahkan ke favorit.';
        }

        $jumlah = Favorite::where('product_id', $product->id)->count();

        // Permintaan dari tombol favorit memakai fetch() supaya tidak reload halaman,
        // sekaligus tetap ada fallback untuk submit biasa tanpa JavaScript.
        if ($request->expectsJson()) {
            return response()->json([
                'favorit' => $terfavorit,
                'jumlah' => $jumlah,
                'pesan' => $pesan,
            ]);
        }

        return back()->with('success', $pesan);
    }

    // Halaman "Produk Favorit": daftar produk yang ditandai pengguna
    public function favorit()
    {
        // Pakai join (bukan whereHas) karena urutan "paling baru difavoritkan"
        // membaca kolom favorites.created_at. Pada whereExists tabel favorites
        // tidak di-join ke query luar, sehingga MySQL menolak order by-nya.
        $products = Product::query()
            ->join('favorites', 'favorites.product_id', '=', 'products.id')
            ->where('favorites.user_id', Auth::id())
            ->with('category')
            ->orderByDesc('favorites.created_at')
            ->select('products.*')
            ->get();

        return view('toko.favorit', compact('products'));
    }

    // Memproses pembelian produk (Form POST dengan Flash Message & Database Transaction)
    public function buyProduct(Request $request, $product_id)
    {
        $product = Product::findOrFail($product_id);

        // Ambil quantity dari input (default 1 jika tidak diisi), minimal 1
        $quantity = max(1, (int) $request->input('quantity', 1));

        // Validasi stok produk cukup untuk jumlah yang diminta
        if ($product->stock < $quantity) {
            return back()->with('error', 'Stok produk tidak mencukupi untuk jumlah yang diminta. Stok tersisa: ' . $product->stock . '.');
        }

        $paymentMethod = $request->input('payment_method', 'online');
        $total = $product->price * $quantity;

        // Stok, transaksi, dan detail disimpan atomik supaya tidak ada
        // transaksi tanpa detail (atau stok berkurang tanpa transaksi).
        DB::transaction(function () use ($product, $quantity, $paymentMethod, $total) {
            // Kurangi stok produk
            $product->stock -= $quantity;
            $product->save();

            // Simpan record transaksi ke database
            $transaction = Transaction::create([
                'customer_id' => $this->customerResolver->customerIdFor(Auth::user()),
                'employee_id' => null,
                'date' => now(),
                'total' => $total,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
            ]);

            // Simpan item belanja ke transaction_details
            // (tidak ada tabel keranjang: 1 pembelian = 1 produk + qty,
            //  bila ada keranjang multi-item nanti cukup loop per baris)
            $transaction->details()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'subtotal' => $total,
            ]);
        });

        // Kembali dengan pesan sukses
        return back()->with('success', 'Selamat! Pembelian berhasil. Stok tersisa: ' . $product->stock . '. Total bayar: Rp ' . number_format($total, 0, ',', '.'));
    }

    // Helper kecil untuk mengecek apakah produk sudah ada di favorit pengguna
    private function apakahFavorit(Product $product): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return Favorite::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->exists();
    }
}
