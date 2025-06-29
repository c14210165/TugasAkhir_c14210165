<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Mail\TestMail;

/*
|--------------------------------------------------------------------------
| Web Routes - Laravel 11 + Sanctum + Vue SPA
|--------------------------------------------------------------------------
|
| Semua route frontend akan diarahkan ke `app.blade.php` agar bisa
| dimount oleh Vue Router.
|
*/

// Login & Logout (POST)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Route test email
Route::get('/test-email', function () {
    $recipientEmail = "c14210165@john.petra.ac.id"; 
    $emailContent = "Ini adalah email tes dari aplikasi Laravel.";

    try {
        Mail::raw($emailContent, function ($message) use ($recipientEmail) {
            $message->to($recipientEmail);
            $message->subject('Tes Koneksi Email Laravel Berhasil');
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        });

        return "Email tes berhasil dikirim ke {$recipientEmail}!";
    } catch (Exception $e) {
        return "Gagal mengirim email. Error: <pre>{$e->getMessage()}</pre>";
    }
});

// Fallback SPA untuk Vue
Route::get('/{any}', function () {
    return view('app'); // view: resources/views/app.blade.php
})->where('any', '.*');
