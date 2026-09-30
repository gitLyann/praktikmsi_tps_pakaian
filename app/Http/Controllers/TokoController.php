<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
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

    return view('toko.index', compact('products', 'categories'));
    }

    // Halaman "Pesanan Saya": riwayat transaksi milik pengguna yang sedang login
    public function pesanan()
    {
        $transactions = Transaction::where('customer_id', auth()->id())
            ->latest('date')
            ->get();

        return view('toko.pesanan', compact('transactions'));
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
}
