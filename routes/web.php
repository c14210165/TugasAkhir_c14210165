<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Mail; // <-- Import Facade Mail  
use App\Mail\TestMail;

Route::get('/kirim-test-email', function () {
    Mail::to('c14210165@john.petra.ac.id')->send(new TestMail());
    return 'Email dikirim!';
});

Route::get('/{any}', function () {
    return view('app');  // Pastikan ada resources/views/app.blade.php yang load Vue build
})->where('any', '.*');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/test-email', function () {
    
    // 1. Tentukan alamat email tujuan untuk tes
    // GANTI INI DENGAN ALAMAT EMAIL ASLI ANDA UNTUK MENERIMA TES
    $recipientEmail = "c14210165@john.petra.ac.id"; 

    // 2. Tulis isi email sederhana
    $emailContent = "Ini adalah email tes dari aplikasi Laravel saya. Jika Anda menerima ini, berarti konfigurasi Brevo di file .env sudah benar!";

    try {
        // 3. Kirim email teks biasa menggunakan Mail::raw()
        Mail::raw($emailContent, function ($message) use ($recipientEmail) {
            $message->to($recipientEmail);
            $message->subject('Tes Koneksi Email Laravel Berhasil');
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        });
        
        // 4. Tampilkan pesan sukses jika tidak ada error
        return "Email tes berhasil dikirim ke {$recipientEmail}! Silakan cek kotak masuk Anda (dan folder spam).";

    } catch (Exception $e) {
        // 5. Jika terjadi error koneksi, tampilkan pesan errornya
        return "Gagal mengirim email. Error: <pre>{$e->getMessage()}</pre>";
    }
});
