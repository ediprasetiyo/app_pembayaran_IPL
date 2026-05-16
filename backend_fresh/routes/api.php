<?php

use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IplController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PengaduanController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WargaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        try {
            \DB::connection()->getPdo();
            return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error'], 500);
        }
    });

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
        Route::post('/ipl/tagihan/{tagihan}/bayar-manual', [IplController::class, 'bayarManual']);
        Route::get('/ipl/pembayaran', [IplController::class, 'riwayatPembayaran']);
        Route::get('/ipl/pembayaran/{pembayaran}', [IplController::class, 'statusPembayaran']);

        // Pengaduan
        Route::get('/pengaduan', [PengaduanController::class, 'index']);
        Route::post('/pengaduan', [PengaduanController::class, 'store']);
        Route::get('/pengaduan/{pengaduan}', [PengaduanController::class, 'show']);
        Route::put('/pengaduan/{pengaduan}', [PengaduanController::class, 'update']);

        // News - semua user bisa baca, admin bisa CRUD
        Route::get('/news', [NewsController::class, 'index']);
        Route::get('/news/latest', [NewsController::class, 'latest']);
        Route::get('/news/{news}', [NewsController::class, 'show']);
        Route::post('/news/{news}/like', [NewsController::class, 'toggleLike']);
        Route::get('/news/{news}/comments', [NewsController::class, 'comments']);
        Route::post('/news/{news}/comments', [NewsController::class, 'storeComment']);
        Route::delete('/news/comments/{comment}', [NewsController::class, 'deleteComment']);
        Route::post('/news/{news}/share', [NewsController::class, 'share']);

        // Notifikasi
        Route::get('/notifikasi', [NotifikasiController::class, 'index']);
        Route::put('/notifikasi/{notifikasi}/read', [NotifikasiController::class, 'markRead']);
        Route::put('/notifikasi/read-all', [NotifikasiController::class, 'markAllRead']);
        Route::delete('/notifikasi/{notifikasi}', [NotifikasiController::class, 'destroy']);

        // AI Assistant (untuk staff backoffice)
        Route::middleware('admin')->post('/ai/ask', [AiAssistantController::class, 'ask']);

        // Admin routes
        Route::middleware('admin')->group(function () {
            Route::get('/admin/dashboard', [WargaController::class, 'dashboard']);
            Route::post('/admin/tagihan/generate', [IplController::class, 'generateTagihan']);
            Route::get('/admin/tagihan/export', [IplController::class, 'exportTagihan']);
            Route::apiResource('/warga', WargaController::class);
            Route::post('/warga/{warga}/keluarga', [WargaController::class, 'addAnggotaKeluarga']);
            Route::put('/warga/{warga}/keluarga/{anggota}', [WargaController::class, 'updateAnggotaKeluarga']);
            Route::delete('/warga/{warga}/keluarga/{anggota}', [WargaController::class, 'deleteAnggotaKeluarga']);

            // News management (admin only)
            Route::post('/news', [NewsController::class, 'store']);
            Route::put('/news/{news}', [NewsController::class, 'update']);
            Route::post('/news/{news}', [NewsController::class, 'update']); // utk multipart form-data dgn _method=PUT
            Route::delete('/news/{news}', [NewsController::class, 'destroy']);
        });

        // Super Admin only — User & Role Management
        Route::middleware('super_admin')->prefix('admin')->group(function () {
            Route::apiResource('/users', UserController::class);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        });
    });
});
