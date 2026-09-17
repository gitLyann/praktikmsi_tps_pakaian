<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\TokoController;

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

    // Dashboard Kasir & Form Restock
    Route::get('/kasir/dashboard', [KasirController::class, 'index'])->name('kasir.dashboard');
    Route::post('/kasir/restock', [KasirController::class, 'storeRestock'])->name('kasir.restock.store');

    // Dashboard Admin/Manager & Approval OAS
    Route::get('/admin/dashboard', [ManagerController::class, 'index'])->name('admin.dashboard');
    Route::put('/admin/restock/{id}', [ManagerController::class, 'updateStatus'])->name('admin.restock.update');

});
