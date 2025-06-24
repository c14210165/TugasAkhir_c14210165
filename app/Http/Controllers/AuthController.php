<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Enums\UserRole;

class AuthController extends Controller
{
    // Login
    public function login(Request $request)
    {
        // 1. Validasi input dari frontend.
        // Kita harapkan frontend mengirim satu field bernama 'login' yang bisa berisi email/username.
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required',
        ]);

        // 2. Tentukan tipe kredensial: apakah 'email' atau 'username'.
        $loginInput = $request->input('login');

        // Gunakan fungsi PHP filter_var untuk mengecek format email.
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // 3. Siapkan array kredensial untuk dicocokkan.
        $credentials = [
            $fieldType => $loginInput,
            'password' => $request->input('password')
        ];

        // 4. Coba lakukan otentikasi dengan kredensial yang sudah disiapkan.
        if (!Auth::attempt($credentials)) {
            // Jika gagal, kirim response error yang lebih umum.
            return response()->json(['message' => 'Kredensial yang diberikan salah.'], 401);
        }

        // 5. Jika otentikasi berhasil, lanjutkan proses.
        // Regenerate session untuk keamanan (mencegah session fixation).
        $request->session()->regenerate();

        // Ambil data user yang berhasil login.
        $user = Auth::user();

        // Tentukan halaman redirect berdasarkan role user.
        $redirectTo = '';
        switch ($user->role) {
            case UserRole::PTIK: // PTIK admin
                $redirectTo = '/request';
                break;
            case UserRole::TU: // TU staff
                $redirectTo = '/tureq';
                break;
            case UserRole::USER: // Regular user
            default:
                $redirectTo = '/userreq';
                break;
        }

        // Kembalikan response sukses.
        return response()->json([
            'message'     => 'Login berhasil!',
            'redirect_to' => $redirectTo,
        ]);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout(Request $request)
    {
        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}

