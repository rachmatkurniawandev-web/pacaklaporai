<?php

use App\Http\Controllers\Api\Admin\AgencyManagementController;
use App\Http\Controllers\Api\Admin\ImageEnhancementController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HotspotController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StatistikController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES
// ============================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ============================================================
// PROTECTED ROUTES
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/user',    [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Laporan CRUD
    Route::apiResource('laporan', LaporanController::class);
    Route::post('laporan/{id}/upload-foto',  [LaporanController::class, 'uploadFoto']);
    Route::put('laporan/{id}/status-change', [LaporanController::class, 'statusChange']);
    Route::post('laporan/{id}/rating',       [LaporanController::class, 'storeRating']);

    // Profile
    Route::get('/profile',  [ProfileController::class, 'show']);
    Route::put('/profile',  [ProfileController::class, 'update']);
    Route::post('/profile', [ProfileController::class, 'update']);

    // Notifikasi - read-all HARUS di atas {id}/read
    Route::get('/notifikasi',           [NotifikasiController::class, 'index']);
    Route::put('/notifikasi/read-all',  [NotifikasiController::class, 'markAllAsRead']);
    Route::put('/notifikasi/{id}/read', [NotifikasiController::class, 'markAsRead']);

    // Hotspot & Statistik
    Route::get('/hotspot',       [HotspotController::class, 'index']);
    Route::get('/statistik',     [StatistikController::class, 'index']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // ============================================================
    // ADMIN ROUTES - prefix /api/admin/...
    // ============================================================
    Route::prefix('admin')->group(function () {

        // Image Enhancement
        // export HARUS di atas {id}
        Route::post('foto/{id}/enhance',        [ImageEnhancementController::class, 'enhance']);
        Route::get('foto/{id}/enhance-history', [ImageEnhancementController::class, 'history']);
        Route::delete('foto/{id}/enhance',      [ImageEnhancementController::class, 'reset']);

        // User Management
        // export HARUS di atas {id}
        Route::get('users/export',  [UserManagementController::class, 'export']);
        Route::get('users',         [UserManagementController::class, 'index']);
        Route::post('users',        [UserManagementController::class, 'store']);
        Route::get('users/{id}',    [UserManagementController::class, 'show']);
        Route::put('users/{id}',    [UserManagementController::class, 'update']);
        Route::delete('users/{id}', [UserManagementController::class, 'destroy']);

        // Agency Management
        Route::get('dinas',         [AgencyManagementController::class, 'index']);
        Route::post('dinas',        [AgencyManagementController::class, 'store']);
        Route::get('dinas/{id}',    [AgencyManagementController::class, 'show']);
        Route::put('dinas/{id}',    [AgencyManagementController::class, 'update']);
        Route::delete('dinas/{id}', [AgencyManagementController::class, 'destroy']);

    });
});
