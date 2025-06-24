<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\ItemType;
use App\Enums\LoanStatus;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Rencanakan peminjaman untuk beberapa hari ke depan
        $startDate = fake()->dateTimeBetween('+2 days', '+10 days');
        $endDate = (clone $startDate)->modify('+'.rand(1, 5).' days');

        return [
            // Kolom-kolom ini akan diisi secara spesifik di Seeder
            'requester_id' => null, 
            'created_by_id' => null,
            'item_id' => null,

            // Data permohonan awal
            'item_type' => fake()->randomElement(array_column(ItemType::cases(), 'value')),
            'location' => fake()->address(),
            'purpose' => fake()->sentence(8),
            'start_at' => $startDate, // Menggunakan nama kolom baru
            'end_at' => $endDate,     // Menggunakan nama kolom baru

            // Semua kolom lain defaultnya adalah null atau nilai awal
            'unit_approver_id' => null,
            'ptik_approver_id' => null,
            'rejection_reason' => null,
            'checked_out_by_id' => null,
            'checked_in_by_id' => null,
            'return_condition' => null,
            'return_notes' => null,
            'is_late' => false, // Default tidak terlambat
            'fine' => 0.00,
            'status' => LoanStatus::PENDING_UNIT->value,
            'responded_at' => null,
            'borrowed_at' => null,
            'returned_at' => null,
        ];
    }

    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            
            // [DIUBAH] Bungkus hasil Faker dengan Carbon::instance()
            // agar menjadi objek Carbon, bukan DateTime biasa.
            $createdAt = Carbon::instance(fake()->dateTimeBetween('-1 year', '-1 month'));

            // Sekarang semua variabel tanggal adalah objek Carbon,
            // dan method ->addDays() akan berjalan lancar.
            $approvedAt = (clone $createdAt)->addDays(rand(1, 2));
            $borrowedAt = (clone $approvedAt)->addDays(rand(1, 2));
            $endDate = (clone $borrowedAt)->addDays(rand(3, 7)); // Durasi pinjam
            $returnedAt = (clone $endDate)->addDays(rand(-1, 3)); // Bisa tepat waktu, bisa telat

            return [
                'status' => LoanStatus::COMPLETED,
                'created_at' => $createdAt,
                'updated_at' => $returnedAt,
                'responded_at' => $approvedAt,
                'borrowed_at' => $borrowedAt,
                'returned_at' => $returnedAt,
                'start_at' => $borrowedAt,
                'end_at' => $endDate,
                'is_late' => $returnedAt > $endDate,
                'return_condition' => 'BAIK',
            ];
        });
    }
}
