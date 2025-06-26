<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\LoanStatus;
use App\Models\User;
use App\Models\ItemType;
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
        $itemTypes = ItemType::pluck('name');
        $startDate = fake()->dateTimeBetween('+2 days', '+10 days');
        $endDate = (clone $startDate)->modify('+'.rand(1, 5).' days');

        return [
            // Kita set ke user acak sebagai default, tapi ini akan ditimpa di seeder.
            'requester_id' => User::factory(),
            'created_by_id' => User::factory(),
            
            // --- LOGIKA BARU UNTUK ORIGINAL REQUESTER ---
            // Secara default, pemohon asli adalah sama dengan pemohon awal.
            // Kita gunakan closure agar nilainya dinamis berdasarkan requester_id.
            'original_requester_id' => function (array $attributes) {
                return $attributes['requester_id'];
            },

            'item_type' => fake()->randomElement($itemTypes),

            'location' => fake()->address(),
            'purpose' => fake()->sentence(8),
            'start_at' => $startDate,
            'end_at' => $endDate,

            // Kolom lainnya biarkan null/default
            'status' => LoanStatus::PENDING_UNIT->value,
            'is_late' => false,
            // ...
        ];
    }

    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            
            $createdAt = Carbon::instance(fake()->dateTimeBetween('-1 year', '-1 month'));

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
