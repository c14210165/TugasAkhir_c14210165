<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Enums\UserRole;

class UserController extends Controller
{
    /**
     * Method #1: Menampilkan Daftar Admin (PTIK)
     * Dipanggil oleh: GET /api/users
     * Tujuan: Mengisi tabel utama di halaman "Manajemen Admin" Anda.
     */
    public function index(Request $request)
    {
        // Cari unit dengan nama "PTIK"
        $ptikUnit = Unit::where('name', 'PTIK')->first();

        if (!$ptikUnit) {
            return response()->json(['message' => 'Unit PTIK tidak ditemukan'], 404);
        }

        // Ambil user yang unit-nya PTIK
        $query = User::query()->where('unit_id', $ptikUnit->id);

        // Tambahkan fitur pencarian
        if ($request->has('search')) {
            $searchTerm = $request->query('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('username', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Ambil data dengan paginasi
        $admins = $query->paginate($request->query('perPage', 10));

        return response()->json($admins);
    }

    /**
     * Method #2: Mencari Kandidat Admin
     * Dipanggil oleh: GET /api/users/search
     * Tujuan: Mengisi daftar hasil pencarian di dalam modal "Tambah Admin".
     */
    public function searchCandidates(Request $request)
    {
        // Validasi bahwa ada input pencarian
        $request->validate(['search' => 'required|string|min:3']);
        $searchTerm = $request->query('search');

        // Cari user yang rolenya BUKAN PTIK
        $candidates = User::query()
            ->where('role', '!=', UserRole::PTIK->value)
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            })
            ->limit(5)
            ->get(['id', 'name', 'email', 'role']); // Ambil hanya data yang perlu ditampilkan

        return response()->json($candidates);
    }

    public function getSelectionList()
    {
        $loggedInUser = Auth::user();

        // Mulai query
        $query = User::query()->select('id', 'name');

        // --- LOGIKA BARU BERDASARKAN PERAN ---
        if ($loggedInUser->role === UserRole::USER) {
            // Jika yang login adalah user biasa, hanya tampilkan dirinya sendiri
            $query->where('id', $loggedInUser->id);
        } else {
            // Jika admin (TU/PTIK), tampilkan semua user dan urutkan berdasarkan nama
            $query->orderBy('name', 'asc');
        }

        // Ambil hasilnya
        $users = $query->get();

        return response()->json($users);
    }

    /**
     * Method #3: Menyimpan Admin Baru (dari form "Buat Admin Baru")
     * Dipanggil oleh: POST /api/users
     * Tujuan: Membuat user baru yang rolenya langsung PTIK.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'        => 'required|string|max:255',
            'username'    => 'required|string|max:255|alpha_dash|unique:users,username',
            'email'       => 'required|string|email|max:255|unique:users,email',
            'password'    => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
            'description' => 'required|string|max:255', // <-- DIUBAH: Sekarang wajib diisi
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['role'] = UserRole::PTIK->value;

        $user = User::create($validatedData);
        return response()->json(['message' => 'Admin baru berhasil dibuat!', 'user' => $user], 201);
    }

    /**
     * Method #4: Mengupdate Admin yang Sudah Ada
     * Dipanggil oleh: PUT /api/users/{user}
     * Tujuan: Menyimpan perubahan dari form modal "Edit User".
     */
    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email'      => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'   => ['nullable', 'confirmed', Password::min(8)], // Password opsional
            'role'       => ['required', Rule::in(array_column(UserRole::cases(), 'value'))], // Tetap validasi role
            'description'=> 'nullable|string',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        return response()->json(['message' => 'User berhasil diupdate!', 'user' => $user]);
    }

    /**
     * Method #5: Mempromosikan User menjadi Admin
     * Dipanggil oleh: PUT /api/users/{user}/promote
     * Tujuan: Aksi untuk tombol "Jadikan Admin" di hasil pencarian modal.
     */
    public function promote(Request $request, User $user)
    {
        // Tambahkan validasi untuk description
        $request->validate(['description' => 'required|string|max:255']);

        // Update role dan description sekaligus
        $user->update([
            'role' => UserRole::PTIK->value,
            'description' => $request->input('description')
        ]);
        
        return response()->json(['message' => "User {$user->name} berhasil dijadikan admin."]);
    }

    /**
     * Method #6: Mencabut Hak Akses Admin (Demote)
     * Dipanggil oleh: DELETE /api/users/{user}/demote
     * Tujuan: Aksi untuk tombol "Cabut Akses" di tabel utama.
     */
    public function demote(User $user)
    {
        if (Auth::id() === $user->id) {
            return response()->json(['message' => 'Anda tidak bisa mencabut hak akses diri sendiri.'], 403);
        }
        
        // Saat di-demote, kembalikan role & kosongkan description
        $user->update([
            'role' => UserRole::USER->value,
            'description' => null
        ]);

        return response()->json(['message' => "Hak akses admin untuk {$user->name} berhasil dicabut."]);
    }
}
