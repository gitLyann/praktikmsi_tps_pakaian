<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StaffController;
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

// Purchase Transaction
Route::post('/toko/buy/{product_id}', [TokoController::class, 'buyProduct'])->name('toko.buy');

    // Dashboard Kasir & Form Restock
    Route::get('/staff/dashboard', [StaffController::class, 'index'])->name('staff.dashboard');
    Route::post('/staff/restock', [StaffController::class, 'storeRestock'])->name('staff.restock.store');

    // Dashboard Admin/Manager & Approval OAS
    Route::get('/admin/dashboard', [ManagerController::class, 'index'])->name('admin.dashboard');
    Route::put('/admin/restock/{id}', [ManagerController::class, 'updateStatus'])->name('admin.restock.update');

});
