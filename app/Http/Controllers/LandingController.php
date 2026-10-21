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
 * dua section rekomendasi: "Rekomendasi untukmu" (personal, untuk pelanggan
 * yang sudah login) dan "Produk Terlaris" (berdasarkan penjualan nyata).
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

        $bestSelling = $recommendations->bestSelling(4);

        return view('welcome', compact('products', 'categories', 'stats', 'recommended', 'bestSelling', 'isPersonal'));
    }
}
