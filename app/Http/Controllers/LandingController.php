<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductRecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landing page La Mamora.
 *
 * Selain katalog, kategori, dan statistik, halaman ini menampilkan
 * rekomendasi produk. Pelanggan yang sudah login mendapat rekomendasi
 * berdasarkan riwayat belanja, pengunjung umum mendapat produk terlaris.
 */
class LandingController extends Controller
{
    public function __invoke(Request $request, ProductRecommendationService $recommendations): View
    {
        $products = Product::with('category')->latest()->get();
        $categories = Category::orderBy('name')->get();
        $stats = [
            'produk' => Product::count(),
            'pelanggan' => User::where('role', 'pelanggan')->count(),
            'kategori' => Category::count(),
        ];

        $user = $request->user();
        $isPersonal = $user !== null && $user->role === 'pelanggan';

        $recommended = $isPersonal
            ? $recommendations->recommendFor($user)
            : $recommendations->recommendForGuest();

        return view('welcome', compact('products', 'categories', 'stats', 'recommended', 'isPersonal'));
    }
}
