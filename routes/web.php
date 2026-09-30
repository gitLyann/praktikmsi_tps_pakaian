<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\ChatbotController;

// Halaman Utama / Landing Page
Route::get('/', function () {
    return view('welcome');
});

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
    Route::get('/staff/dashboard', [StaffController::class, 'index'])->name('staff.dashboard');
    Route::post('/staff/restock', [StaffController::class, 'storeRestock'])->name('staff.restock.store');
    Route::post('/staff/products', [StaffController::class, 'storeProduct'])->name('staff.products.store');

    // Update master produk oleh Staff (ubah deskripsi, foto, harga, kategori, dll.)
    Route::put('/staff/products/{product}', [StaffController::class, 'updateProduct'])->name('staff.products.update');

    // Dashboard Admin/Manager & Approval OAS
    Route::get('/admin/dashboard', [ManagerController::class, 'index'])->name('admin.dashboard');
    Route::put('/admin/restock/{id}', [ManagerController::class, 'updateStatus'])->name('admin.restock.update');

    // Kelola produk oleh Manager: ubah dan hapus (RUD).
    // Penambahan master produk dilakukan lewat dashboard Staff, jadi tidak ada
    // route POST /admin/products di sini.
    Route::put('/admin/products/{product}', [ManagerController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [ManagerController::class, 'destroyProduct'])->name('admin.products.destroy');

    // Chatbot API
    Route::post('/chatbot/message', [ChatbotController::class, 'message'])->name('chatbot.message');

});
