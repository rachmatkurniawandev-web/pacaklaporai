<?php

use App\Http\Controllers\Api\HotspotController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StatistikController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================
// PUBLIC ROUTES (tidak perlu token)
// ============================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ============================================================
// PROTECTED ROUTES (wajib kirim token Bearer)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Auth ---
    Route::get('/user',    [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Laporan CRUD ---
    Route::apiResource('laporan', LaporanController::class);
    Route::post('laporan/{id}/upload-foto',  [LaporanController::class, 'uploadFoto']);
    Route::put('laporan/{id}/status-change', [LaporanController::class, 'statusChange']);
    Route::post('laporan/{id}/rating',       [LaporanController::class, 'storeRating']);

    // --- Profile ---
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // --- Notifikasi ---
    Route::get('/notifikasi',              [NotifikasiController::class, 'index']);
    Route::put('/notifikasi/{id}/read',    [NotifikasiController::class, 'markAsRead']);

    // --- Hotspot (Peta Titik Lapor) ---
    // GET /api/hotspot
    // GET /api/hotspot?kategori_id=2
    // GET /api/hotspot?status=pending
    // GET /api/hotspot?radius=300&limit=20
    Route::get('/hotspot', [HotspotController::class, 'index']);

    // --- Statistik Dashboard ---
    // GET /api/statistik
    // Menggantikan angka hardcoded di dashboard Flutter
    Route::get('/statistik', [StatistikController::class, 'index']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
});
