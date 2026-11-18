<?php

use App\Http\Controllers\RestockRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:staff,admin'])->group(function () {
    Route::get('/oas/restock', [RestockRequestController::class, 'index']);
    Route::post('/oas/restock', [RestockRequestController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::put('/oas/restock/{id}/status', [RestockRequestController::class, 'updateStatus']);
});
