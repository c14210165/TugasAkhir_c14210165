<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Item;
use App\Models\Loan;
use App\Enums\UserRole;
use App\Enums\LoanStatus;
use App\Enums\ItemStatus;
use Carbon\Carbon;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil data master
        $users = User::where('role', UserRole::USER->value)->get();
        $admins = User::whereIn('role', [UserRole::TU->value, UserRole::PTIK->value])->get();
        $ptikAdmins = User::where('role', UserRole::PTIK->value)->get();
        $availableItems = Item::where('status', 'AVAILABLE')->get()->shuffle();

        if ($users->isEmpty() || $admins->isEmpty() || $availableItems->isEmpty()) {
            $this->command->warn('Data User, Admin, atau Item tidak cukup. Pastikan seeder lain sudah dijalankan.');
            return;
        }
        
        $this->command->info('Memulai Seeding untuk Tabel Peminjaman...');
        
        // Skenario 1 & 2: 10 Permohonan masih PENDING (baik di TU maupun PTIK)
        // Kita hanya perlu definisikan requester dan creator. Original requester akan mengikuti requester_id.
        Loan::factory(5)->create([
            'requester_id' => fn() => $users->random()->id,
            'created_by_id' => fn() => $users->random()->id, // Dibuat oleh user sendiri
            'status' => LoanStatus::PENDING_UNIT,
        ]);
        Loan::factory(5)->create([
            'requester_id' => fn() => $users->random()->id,
            'created_by_id' => fn() => $admins->random()->id, // Dibuatkan oleh admin
            'status' => LoanStatus::PENDING_PTIK,
            'unit_approver_id' => fn() => $admins->random()->id,
            'responded_at' => now()->subDay(),
        ]);
        $this->command->info('10 data PENDING berhasil dibuat.');

        // Skenario 3, 4, 5: Buat 15 peminjaman yang sudah diproses (APPROVED, ACTIVE, COMPLETED)
        for ($i = 0; $i < 15; $i++) {
            if ($availableItems->isEmpty()) break;
            $item = $availableItems->pop();
            $requester = $users->random();

            $loanData = [
                'requester_id' => $requester->id,
                // original_requester_id akan otomatis sama dengan requester_id
                'created_by_id' => $admins->random()->id,
                'item_id' => $item->id,
                'item_type' => $item->itemType->name, // Mengambil dari relasi
                'unit_approver_id' => $admins->random()->id,
                'ptik_approver_id' => $ptikAdmins->random()->id,
                'checked_out_by_id' => $admins->random()->id,
                'responded_at' => now()->subDays(rand(10, 20)),
            ];

            // Buat data dengan status berbeda-beda
            if ($i < 5) { // 5 data APPROVED
                $loanData['status'] = LoanStatus::APPROVED;
                $item->update(['status' => ItemStatus::BORROWED]);
            } elseif ($i < 10) { // 5 data ACTIVE
                $loanData['status'] = LoanStatus::ACTIVE;
                $loanData['borrowed_at'] = now()->subDays(rand(1, 5));
                $item->update(['status' => ItemStatus::BORROWED]);
            } else { // 5 data COMPLETED
                $loanData['status'] = LoanStatus::COMPLETED;
                $loanData['borrowed_at'] = now()->subDays(rand(10, 15));
                $loanData['returned_at'] = now()->subDays(rand(1, 9));
                $loanData['checked_in_by_id'] = $admins->random()->id;
                $loanData['is_late'] = Carbon::parse($loanData['returned_at'])->isAfter($loanData['end_at']);
                $item->update(['status' => ItemStatus::AVAILABLE]);
            }
            
            Loan::factory()->create($loanData);
        }
        $this->command->info('15 data (APPROVED, ACTIVE, COMPLETED) berhasil dibuat.');

        $this->command->info('Seeding data peminjaman selesai.');
    }
}
