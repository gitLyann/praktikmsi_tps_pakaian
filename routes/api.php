<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestockRequestController;

Route::get('/oas/restock', [RestockRequestController::class, 'index']);
Route::post('/oas/restock', [RestockRequestController::class, 'store']);
Route::put('/oas/restock/{id}/status', [RestockRequestController::class, 'updateStatus']);
