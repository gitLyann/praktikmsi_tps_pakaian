<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DssAnalysisController;
use App\Http\Controllers\DssScoreController;
use App\Http\Controllers\DssSupplierController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TokoController;
use Illuminate\Support\Facades\Route;

// Halaman Utama / Landing Page
Route::get('/', LandingController::class)->name('landing');

// Route Auth
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// Halaman Berdasarkan Role (Hanya bisa dibuka jika sudah login)
Route::middleware(['auth'])->group(function () {

    // Halaman Pembeli / Catalog Pakaian (TPS)
    Route::get('/toko', [TokoController::class, 'index'])->name('toko.index');

    // Riwayat Pesanan Saya (transaksi milik user yang login)
    Route::get('/toko/pesanan', [TokoController::class, 'pesanan'])->name('toko.pesanan');

    // Purchase Transaction
    Route::post('/toko/buy/{product_id}', [TokoController::class, 'buyProduct'])->name('toko.buy');

    // Detail produk (foto + deskripsi lengkap)
    Route::get('/toko/produk/{product}', [TokoController::class, 'show'])->name('toko.show');

    // Produk Favorit: daftar & aksi tambah/hapus favorit
    Route::get('/toko/favorit', [TokoController::class, 'favorit'])->name('toko.favorit');
    Route::post('/toko/favorit/{product}', [TokoController::class, 'toggleFavorite'])->name('toko.favorit.toggle');

    // Dashboard Kasir & Form Restock
    Route::middleware(['role:staff,admin'])->group(function () {
        Route::get('/staff/dashboard', [StaffController::class, 'index'])->name('staff.dashboard');
        Route::post('/staff/restock', [StaffController::class, 'storeRestock'])->name('staff.restock.store');
        Route::post('/staff/products', [StaffController::class, 'storeProduct'])->name('staff.products.store');

        // Update master produk oleh Staff (ubah deskripsi, foto, harga, kategori, dll.)
        Route::put('/staff/products/{product}', [StaffController::class, 'updateProduct'])->name('staff.products.update');
    });

    // Dashboard Admin/Manager & Approval OAS
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', [ManagerController::class, 'index'])->name('admin.dashboard');
        Route::put('/admin/restock/{id}', [ManagerController::class, 'updateStatus'])->name('admin.restock.update');

        // Kelola produk oleh Manager: ubah dan hapus (RUD).
        // Penambahan master produk dilakukan lewat dashboard Staff, jadi tidak ada
        // route POST /admin/products di sini.
        Route::put('/admin/products/{product}', [ManagerController::class, 'updateProduct'])->name('admin.products.update');
        Route::delete('/admin/products/{product}', [ManagerController::class, 'destroyProduct'])->name('admin.products.destroy');
    });

    // DSS - Penilaian pemasok oleh Staff (Kasir)
    Route::middleware(['role:staff,admin'])->group(function () {
        Route::get('/staff/dss', [DssScoreController::class, 'index'])->name('staff.dss');
        Route::post('/staff/dss/skor', [DssScoreController::class, 'store'])->name('staff.dss.scores.store');
        Route::post('/staff/dss/restock', [DssScoreController::class, 'restockRequest'])->name('staff.dss.restock.store');
    });

    // DSS - Analisis & hasil ranking oleh Admin (Manager)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dss', [DssAnalysisController::class, 'summary'])->name('admin.dss');
        Route::get('/admin/dss/pemasok', [DssAnalysisController::class, 'suppliers'])->name('admin.dss.suppliers.data');
        Route::post('/admin/dss/analisis', [DssAnalysisController::class, 'run'])->name('admin.dss.run');
        Route::post('/admin/dss/bobot', [DssAnalysisController::class, 'saveWeights'])->name('admin.dss.weights.store');
        Route::post('/admin/dss/restock', [DssAnalysisController::class, 'restockRequest'])->name('admin.dss.restock.store');

        // Kelola data pemasok DSS (master pemasok + penawaran produk).
        Route::post('/admin/dss/pemasok', [DssSupplierController::class, 'store'])->name('admin.dss.suppliers.store');
        Route::put('/admin/dss/pemasok/{supplier}', [DssSupplierController::class, 'update'])->name('admin.dss.suppliers.update');
        Route::delete('/admin/dss/pemasok/{supplier}', [DssSupplierController::class, 'destroy'])->name('admin.dss.suppliers.destroy');
        Route::post('/admin/dss/pemasok/{supplier}/aktif', [DssSupplierController::class, 'toggle'])->name('admin.dss.suppliers.toggle');
        Route::post('/admin/dss/pemasok/{supplier}/penawaran', [DssSupplierController::class, 'storeOffer'])->name('admin.dss.suppliers.offers.store');
        Route::delete('/admin/dss/pemasok/{supplier}/penawaran/{offer}', [DssSupplierController::class, 'destroyOffer'])->name('admin.dss.suppliers.offers.destroy');
    });

    // Chatbot API
    Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');

});
