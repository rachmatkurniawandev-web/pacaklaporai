<?php

use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes (tidak perlu authentication)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (perlu token authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Laporan CRUD
    Route::apiResource('laporan', LaporanController::class);
    Route::post('laporan/{id}/upload-foto', [LaporanController::class, 'uploadFoto']);
    Route::put('laporan/{id}/status-change', [LaporanController::class, 'statusChange']);
    Route::post('laporan/{id}/rating', [LaporanController::class, 'storeRating']);
});

// Profile Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::put('/notifikasi/{id}/read', [NotifikasiController::class, 'markAsRead']);
});
