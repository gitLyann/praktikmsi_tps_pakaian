<?php

namespace App\Http\Controllers;

use App\Models\Product;

class TokoController extends Controller
{
    // Menampilkan daftar produk dari database ke katalog pembeli
    public function index()
    {
        $products = Product::all();
        return view('toko.index', compact('products'));
    }
}
