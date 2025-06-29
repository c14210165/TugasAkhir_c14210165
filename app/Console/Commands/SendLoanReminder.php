<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoanReminderMail;
use App\Notifications\LoanReminderNotification;
use Illuminate\Support\Carbon;

class SendLoanReminder extends Command
{
    protected $signature = 'loan:reminder';
    protected $description = 'Kirim pengingat email & notifikasi untuk peminjaman yang akan berakhir atau sudah lewat';

    public function handle()
    {
        $now = Carbon::now();
        $soon = $now->copy()->addHours(72); // misal, 72 jam ke depan

        $this->info('Mengecek peminjaman yang hampir selesai atau terlambat...');

        $loans = Loan::with('requester')
            ->whereIn('status', ['APPROVED'])
            ->where(function ($q) use ($soon, $now) {
                $q->where('end_at', '<=', $soon)
                  ->orWhere('end_at', '<', $now);
            })->get();

        $this->info("Ditemukan {$loans->count()} peminjaman.");

        foreach ($loans as $loan) {
            $user = $loan->requester;

            if (!$user || !$user->email) {
                $this->warn("User tidak ditemukan untuk loan ID {$loan->id}");
                continue;
            }

            // Kirim email
            Mail::to($user->email)->send(new LoanReminderMail($loan));
            // Kirim notifikasi
            $user->notify(new LoanReminderNotification($loan));

            $this->info("Pengingat dikirim ke: {$user->email}");
        }

        $this->info("Selesai memproses pengingat.");
    }
}
