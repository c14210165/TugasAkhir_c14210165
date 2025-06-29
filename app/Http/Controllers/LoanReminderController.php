<?php 

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Notifications\LoanReminderNotification;
use App\Mail\LoanReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class LoanReminderController extends Controller
{
    public function checkExpiringOrLateLoans()
    {
        $now = Carbon::now();
        $soon = $now->copy()->addHours(72); // misalnya, akan selesai dalam 6 jam

        // Ambil semua pinjaman yang akan selesai atau lewat
        $loans = Loan::with('requester')
            ->whereIn('status', ['APPROVED']) // atau status lain yang relevan
            ->where(function ($q) use ($soon, $now) {
                $q->where('end_at', '<=', $soon)
                  ->orWhere('end_at', '<', $now);
            })
            ->get();

        $count = 0;

        foreach ($loans as $loan) {
            $user = $loan->requester;

            if (!$user || !$user->email) continue;

            // 1. Kirim Email
            Mail::to($user->email)->send(new LoanReminderMail($loan));

            // 2. Kirim Notifikasi
            $user->notify(new LoanReminderNotification($loan));

            $count++;
        }

        return response()->json([
            'message' => "$count pengingat peminjaman dikirim.",
            'total' => $count
        ]);
    }
}
