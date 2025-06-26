<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Unit;
use App\Enums\UserRole;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitPtik = Unit::where('name', 'PTIK')->first();
        $unitInformatika = Unit::where('name', 'Informatika')->first();

        // Pengecekan keamanan jika unit tidak ditemukan
        if (!$unitPtik || !$unitInformatika) {
            $this->command->error('Unit PTIK atau Informatika tidak ditemukan. Pastikan UnitSeeder sudah dijalankan.');
            return;
        }

        // 1. Membuat user Admin/PTIK dengan data spesifik
        User::create([
            'name' => 'Admin PTIK',
            'username' => 'adminptik',
            'email' => 'ptik@example.com',
            'password' => Hash::make('password'), // Password: password
            'role' => UserRole::PTIK->value,
            'description' => 'Akun Administrator Utama PTIK',
            'unit_id' => $unitPtik->id,
        ]);

        // 2. Membuat user TU dengan data spesifik
        User::create([
            'name' => 'Staf TU',
            'username' => 'staftu',
            'email' => 'tu@example.com',
            'password' => Hash::make('password'), // Password: password
            'role' => UserRole::TU->value,
            'description' => 'Akun Staf Tata Usaha',
            'unit_id' => $unitInformatika->id,
        ]);

        // 3. Membuat 10 user biasa menggunakan Factory yang sudah kita modifikasi
        User::factory(3)->create();
    }
}
