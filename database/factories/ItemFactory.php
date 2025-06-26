<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ItemType;   // <-- Import Enum Tipe
use App\Enums\ItemStatus; // <-- Import Enum Status

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $itemTypes = ItemType::all();
        // Pilih satu objek ItemType secara acak
        $selectedType = $itemTypes->random();

        $brand = fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Epson', 'Canon', 'Sony', 'Acer']);

        // --- LOGIKA BARU UNTUK MEMBUAT NAMA BARANG ---
        $itemName = match ($selectedType->name) {
            'Laptop' => "{$brand} Inspiron " . fake()->numberBetween(14, 15) . " inch",
            'Projector' => "{$brand} PowerLite " . fake()->word(),
            'Camera' => "{$brand} EOS " . fake()->numberBetween(100, 900) . "D",
            'Hardware' => "{$brand} " . fake()->randomElement(['Wireless Mouse', 'Mechanical Keyboard', 'USB Hub']),
            default => "{$brand} " . fake()->word(),
        };

        return [
            // Membuat barcode 13 digit yang unik
            'barcode'     => fake()->unique()->ean13(),

            // Membuat kode barang custom, misal: LP-1234, PR-5678
            'code'        => substr($selectedType->name, 0, 2) . '-' . fake()->unique()->numberBetween(1000, 9999),

            'name'        => $itemName,
            
            // Mengambil brand secara acak dari daftar yang kita tentukan
            'brand'       => $brand,
            
            // Menggunakan tipe barang yang sudah kita ambil di atas
            'item_type_id' => $selectedType->id,
            
            // Mengisi aksesoris secara acak
            'accessories' => fake()->randomElement(['Mouse, Tas, Charger', 'Kabel Power, Remote', 'Lensa Kit, Baterai, Charger']),
            
            // Status default untuk semua barang baru adalah AVAILABLE
            'status'      => ItemStatus::AVAILABLE->value,
        ];
    }
}
