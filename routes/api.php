<?php

use App\Http\Controllers\Api\LaporanController;
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
});