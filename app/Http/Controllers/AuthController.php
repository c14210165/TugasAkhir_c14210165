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
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required',
        ]);

        $loginInput = $request->input('login');
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $loginInput,
            'password' => $request->input('password')
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Kredensial yang diberikan salah.'], 401);
        }

        $request->session()->regenerate(); // Wajib untuk keamanan session

        $user = Auth::user();

        $redirectTo = match ($user->role) {
            UserRole::PTIK => '/request',
            UserRole::TU   => '/tureq',
            default        => '/userreq',
        };

        return response()->json([
            'message'     => 'Login berhasil!',
            'redirect_to' => $redirectTo,
        ]);
    }

    public function me()
    {
        return response()->json(Auth::user());
    }

    public function logout(Request $request)
    {
        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}
