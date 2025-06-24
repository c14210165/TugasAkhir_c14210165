<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\ItemType;   // <-- Import Enum Tipe
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
        // Ambil satu tipe barang secara acak dari Enum
        $type = fake()->randomElement(ItemType::cases());

        return [
            // Membuat barcode 13 digit yang unik
            'barcode'     => fake()->unique()->ean13(),

            // Membuat kode barang custom, misal: LP-1234, PR-5678
            'code'        => substr($type->name, 0, 2) . '-' . fake()->unique()->numberBetween(1000, 9999),

            // Mengambil brand secara acak dari daftar yang kita tentukan
            'brand'       => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Epson', 'Canon', 'Sony', 'Acer']),
            
            // Menggunakan tipe barang yang sudah kita ambil di atas
            'type'        => $type->value,
            
            // Mengisi aksesoris secara acak
            'accessories' => fake()->randomElement(['Mouse, Tas, Charger', 'Kabel Power, Remote', 'Lensa Kit, Baterai, Charger']),
            
            // Status default untuk semua barang baru adalah AVAILABLE
            'status'      => ItemStatus::AVAILABLE->value,
        ];
    }
}
