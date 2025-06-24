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
        $requesters = User::where('role', UserRole::USER->value)->get();
        $creators = User::whereIn('role', [UserRole::TU->value, UserRole::PTIK->value])->get();
        $admins = User::whereIn('role', [UserRole::TU->value, UserRole::PTIK->value])->get();
        $ptikAdmins = User::where('role', UserRole::PTIK->value)->get();
        $allItems = Item::all();

        if ($requesters->isEmpty() || $admins->isEmpty() || $allItems->isEmpty()) {
            $this->command->warn('User, Admin, atau Item tidak ditemukan. Pastikan seeder lain sudah dijalankan.');
            return;
        }

        $this->command->info('Memulai Seeding untuk Tabel Peminjaman...');
        
        // Skenario 1: 5 Permohonan masih PENDING_UNIT
        Loan::factory()->count(5)->create([
            'requester_id' => fn() => $requesters->random()->id,
            'created_by_id' => fn() => $creators->random()->id,
            'status' => LoanStatus::PENDING_UNIT,
        ]);
        $this->command->info('5 data PENDING_UNIT berhasil dibuat.');

        // Skenario 2: 5 Permohonan PENDING_PTIK
        Loan::factory()->count(5)->create([
            'requester_id' => fn() => $requesters->random()->id,
            'created_by_id' => fn() => $creators->random()->id,
            'status' => LoanStatus::PENDING_PTIK,
            'unit_approver_id' => fn() => $admins->random()->id,
            'responded_at' => now()->subDay(),
        ]);
        $this->command->info('5 data PENDING_PTIK berhasil dibuat.');

        // Siapkan item yang tersedia untuk skenario berikutnya
        $availableItems = $allItems->where('status', 'AVAILABLE')->shuffle();

        // Skenario 3: 5 Permohonan disetujui (APPROVED) dan menunggu diambil
        for ($i = 0; $i < 5; $i++) {
            if ($availableItems->isEmpty()) break;
            $item = $availableItems->pop();
            $item->update(['status' => ItemStatus::BORROWED]);

            Loan::factory()->create([
                'requester_id' => $requesters->random()->id,
                'created_by_id' => $creators->random()->id,
                'item_id' => $item->id,
                'item_type' => $item->type,
                'status' => LoanStatus::APPROVED,
                'unit_approver_id' => $admins->random()->id,
                'ptik_approver_id' => $ptikAdmins->random()->id,
                'responded_at' => now(),
            ]);
        }
        $this->command->info('5 data APPROVED berhasil dibuat.');

        // [DITAMBAHKAN] Skenario 4: 5 Peminjaman sedang AKTIF
        for ($i = 0; $i < 5; $i++) {
            if ($availableItems->isEmpty()) {
                $this->command->warn('Item available habis untuk skenario ACTIVE.');
                break;
            }
            $item = $availableItems->pop();
            $item->update(['status' => ItemStatus::BORROWED]);

            Loan::factory()->create([
                'requester_id' => $requesters->random()->id,
                'created_by_id' => $admins->random()->id,
                'item_type' => $item->type,
                'item_id' => $item->id,
                'status' => LoanStatus::ACTIVE,
                'unit_approver_id' => $admins->random()->id,
                'ptik_approver_id' => $ptikAdmins->random()->id,
                'responded_at' => now()->subDays(rand(3, 5)),
                'checked_out_by_id' => $admins->random()->id,
                'borrowed_at' => now()->subDays(rand(1, 2)), // Diambil 1-2 hari lalu
                'end_at' => now()->addDays(rand(3, 7)), // Tenggatnya di masa depan
            ]);
        }
        $this->command->info('5 data ACTIVE berhasil dibuat.');

        // Skenario 5: 5 Permohonan DITOLAK (REJECTED)
        Loan::factory()->count(5)->create([
            'requester_id' => fn() => $requesters->random()->id,
            'created_by_id' => fn() => $creators->random()->id,
            'status' => LoanStatus::REJECTED,
            'ptik_approver_id' => fn() => $ptikAdmins->random()->id,
            'rejection_reason' => 'Stok barang tidak mencukupi.',
        ]);
        $this->command->info('5 data REJECTED berhasil dibuat.');
        
        // Skenario 6: 5 Permohonan DIBATALKAN (CANCELLED)
        Loan::factory()->count(5)->create([
            'requester_id' => fn() => $requesters->random()->id,
            'created_by_id' => fn() => $creators->random()->id,
            'status' => LoanStatus::CANCELLED,
        ]);
        $this->command->info('5 data CANCELLED berhasil dibuat.');

        // Skenario 7: 200 Peminjaman sudah SELESAI (COMPLETED) untuk data historis
        $this->command->info('Membuat 200 data peminjaman historis (COMPLETED)...');
        for ($i = 0; $i < 200; $i++) {
            $item = $allItems->random();
            $admin = $admins->random();
            
            Loan::factory()->completed()->create([
                'requester_id' => $requesters->random()->id,
                'created_by_id' => $admin->id,
                'item_id' => $item->id,
                'item_type' => $item->type,
                'unit_approver_id' => $admin->id,
                'ptik_approver_id' => $ptikAdmins->random()->id,
                'checked_out_by_id' => $admin->id,
                'checked_in_by_id' => $admin->id,
            ]);
        }
        $this->command->info('200 data COMPLETED berhasil dibuat.');

        $this->command->info('Seeding data peminjaman selesai.');
    }
}
