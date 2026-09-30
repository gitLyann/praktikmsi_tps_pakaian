<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TokoController extends Controller
{
    // Menampilkan daftar produk dari database ke katalog pembeli
    public function index()
    {
        $products = Product::with('category')->latest()->get();

    // Dipakai untuk checkbox filter kategori di sidebar katalog
    $categories = Category::orderBy('name')->pluck('name');

    // Daftar id produk yang sudah difavoritkan pengguna, supaya tombol hati
    // di katalog langsung tampil dalam keadaan aktif tanpa request tambahan.
    $favoritIds = Favorite::where('user_id', auth()->id())
        ->pluck('product_id')
        ->all();

    return view('toko.index', compact('products', 'categories', 'favoritIds'));
    }

    // Halaman "Pesanan Saya": riwayat transaksi milik pengguna yang sedang login
    public function pesanan()
    {
        $transactions = Transaction::where('customer_id', auth()->id())
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
        $userId = auth()->id();

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
            ->where('favorites.user_id', auth()->id())
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

        // Ambil quantity dari input (default 1 jika tidak diisi)
        $quantity = $request->input('quantity', 1);

        // Validasi stok produk cukup
        if ($product->stock <= 0) {
            return back()->with('error', 'Stok produk habis, tidak dapat melakukan pembelian.');
        }

        // Kurangi stok produk
        $product->stock -= $quantity;
        $product->save();

        // Simpan record transaksi ke database
        $paymentMethod = $request->input('payment_method', 'online');

        Transaction::create([
            'customer_id' => auth()->user()->id,
            'employee_id' => null,
            'date' => now(),
            'total' => $product->price * $quantity,
            'payment_method' => $paymentMethod,
            'status' => 'completed',
        ]);

        // Kembali dengan pesan sukses
        return back()->with('success', 'Selamat! Pembelian berhasil. Stok tersisa: ' . $product->stock . '. Total bayar: Rp ' . number_format($product->price * $quantity, 0, ',', '.'));
    }

    // Helper kecil untuk mengecek apakah produk sudah ada di favorit pengguna
    private function apakahFavorit(Product $product): bool
    {
        return Favorite::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->exists();
    }
}
