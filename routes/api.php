<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\PrintController;

Route::post('/login', [AuthController::class, 'login']); 

// Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users', [UserController::class, 'index']);

    Route::prefix('users')->group(function () {
        
        // GET /api/users
        // Menampilkan daftar admin (PTIK). Memanggil UserController@index
        Route::get('/', [UserController::class, 'index']);

        // GET /api/users/search
        // Mencari kandidat user untuk dijadikan admin. Memanggil UserController@searchCandidates
        Route::get('/search', [UserController::class, 'searchCandidates']);

        // GET /api/users/selection-list
        Route::get('/selection-list', [UserController::class, 'getSelectionList']);

        // POST /api/users
        // Membuat user baru yang rolenya langsung admin. Memanggil UserController@store
        Route::post('/', [UserController::class, 'store']);

        // PUT /api/users/{user}
        // Mengupdate data seorang admin. Memanggil UserController@update
        Route::put('/{user}', [UserController::class, 'update']);

        // PUT /api/users/{user}/promote
        // Mempromosikan user biasa menjadi admin. Memanggil UserController@promote
        Route::put('/{user}/promote', [UserController::class, 'promote']);
        
        // DELETE /api/users/{user}/demote
        // Mencabut hak akses admin (bukan menghapus user). Memanggil UserController@demote
        Route::delete('/{user}/demote', [UserController::class, 'demote']);
    });

    Route::get('/items/types', [ItemController::class, 'getTypes']);
    Route::get('/items/available', [ItemController::class, 'getAvailable']);
    Route::apiResource('/items', ItemController::class);

    // === ROUTE UNTUK SETIAP HALAMAN PROSES ===

    // Route untuk Halaman Permohonan Baru
    // Frontend akan memanggil GET /api/requests
    Route::get('/requests', [RequestController::class, 'index']);
    Route::put('/requests/{loan}', [RequestController::class, 'update']);
    Route::post('/requests/{loan}/approve', [RequestController::class, 'approve']);
    Route::post('/requests/{loan}/decline', [RequestController::class, 'decline']);
    Route::post('/requests/{loan}/cancel', [RequestController::class, 'cancel']);

    // Route untuk Halaman Peminjaman Aktif
    // Frontend akan memanggil GET /api/loans-active
    Route::get('/loans', [LoanController::class, 'index']);

    // PASTIKAN ROUTE INI ADA
    // Panggilan POST dari Vue ke /api/loans akan ditangani oleh method store()
    Route::post('/loans', [LoanController::class, 'store']);

    Route::post('/loans/{loan}/checkout', [LoanController::class, 'checkout']);
    Route::put('/loans/{loan}', [LoanController::class, 'update']);
    Route::post('/loans/{loan}/cancel', [LoanController::class, 'cancel']);

    // Route untuk Halaman Riwayat Pengembalian
    // Frontend akan memanggil GET /api/returns
    Route::get('/returns', [ReturnController::class, 'index']);
    Route::post('/loans/{loan}/return', [ReturnController::class, 'store']);

    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/loans/{loan}/detail', [LoanController::class, 'show']);

    Route::get('/reports', [ReportController::class, 'report']);

    Route::get('/predictions/generate/{type}', [PredictionController::class, 'generate']);

    Route::post('/types/add', [ItemTypeController::class, 'store']);

    Route::get('/print/handover/{loan}', [PrintController::class, 'handover'])->name('print.handover');

    Route::post('/send-email', [EmailController::class, 'send']);

// });

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});