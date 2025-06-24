<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Enums\UserRole;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Membuat user Admin/PTIK dengan data spesifik
        User::create([
            'name' => 'Admin PTIK',
            'username' => 'adminptik',
            'email' => 'ptik@example.com',
            'password' => Hash::make('password'), // Password: password
            'role' => UserRole::PTIK->value,
            'description' => 'Akun Administrator Utama PTIK'
        ]);

        // 2. Membuat user TU dengan data spesifik
        User::create([
            'name' => 'Staf TU',
            'username' => 'staftu',
            'email' => 'tu@example.com',
            'password' => Hash::make('password'), // Password: password
            'role' => UserRole::TU->value,
            'description' => 'Akun Staf Tata Usaha'
        ]);

        // 3. Membuat 10 user biasa menggunakan Factory yang sudah kita modifikasi
        User::factory(3)->create();
    }
}
