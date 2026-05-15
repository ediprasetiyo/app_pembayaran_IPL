<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IplController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PengaduanController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WargaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth public
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

    // Midtrans callback (no auth needed)
    Route::post('/ipl/midtrans/callback', [IplController::class, 'midtransCallback']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('/auth/change-password', [AuthController::class, 'changePassword']);

        // IPL
        Route::get('/ipl/tagihan', [IplController::class, 'tagihan']);
        Route::get('/ipl/tagihan/bulan-ini', [IplController::class, 'tagihanBulanIni']);
        Route::get('/ipl/tunggakan', [IplController::class, 'tunggakan']);
        Route::post('/ipl/tagihan/{tagihan}/bayar', [IplController::class, 'bayar']);
        Route::get('/ipl/pembayaran', [IplController::class, 'riwayatPembayaran']);
        Route::get('/ipl/pembayaran/{pembayaran}', [IplController::class, 'statusPembayaran']);

        // Pengaduan
        Route::get('/pengaduan', [PengaduanController::class, 'index']);
        Route::post('/pengaduan', [PengaduanController::class, 'store']);
        Route::get('/pengaduan/{pengaduan}', [PengaduanController::class, 'show']);
        Route::put('/pengaduan/{pengaduan}', [PengaduanController::class, 'update']);

        // Notifikasi
        Route::get('/notifikasi', [NotifikasiController::class, 'index']);
        Route::put('/notifikasi/{notifikasi}/read', [NotifikasiController::class, 'markRead']);
        Route::put('/notifikasi/read-all', [NotifikasiController::class, 'markAllRead']);
        Route::delete('/notifikasi/{notifikasi}', [NotifikasiController::class, 'destroy']);

        // Admin routes
        Route::middleware('admin')->group(function () {
            Route::get('/admin/dashboard', [WargaController::class, 'dashboard']);
            Route::apiResource('/warga', WargaController::class);
            Route::post('/warga/{warga}/keluarga', [WargaController::class, 'addAnggotaKeluarga']);
            Route::put('/warga/{warga}/keluarga/{anggota}', [WargaController::class, 'updateAnggotaKeluarga']);
            Route::delete('/warga/{warga}/keluarga/{anggota}', [WargaController::class, 'deleteAnggotaKeluarga']);
        });

        // Super Admin only — User & Role Management
        Route::middleware('super_admin')->prefix('admin')->group(function () {
            Route::apiResource('/users', UserController::class);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        });
    });
});
