<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ItemType;

class ItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus App\Models\ di sini karena sudah di-import di atas
        $types = ['Laptop', 'Projector', 'Camera', 'Hardware'];
        foreach ($types as $type) {
            ItemType::create(['name' => $type]);
        }
    }
}
