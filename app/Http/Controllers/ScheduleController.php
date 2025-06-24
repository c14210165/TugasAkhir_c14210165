<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Enums\LoanStatus;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        // Memulai query dasar.
        // Halaman ini menampilkan semua jadwal yang relevan, tidak peduli siapa yang login.
        $query = Loan::query()->with(['requester:id,name', 'item:id,brand,code'])
            ->whereIn('status', [LoanStatus::APPROVED, LoanStatus::ACTIVE])
            ->whereNotNull('item_id'); // Pastikan hanya yang sudah ada barangnya

        // Terapkan filter tipe barang dari frontend jika ada
        if ($request->has('type') && $request->query('type') !== 'Semua') {
            $query->where('item_type', $request->query('type'));
        }

        $loans = $query->get();

        // Proses mapping untuk diubah menjadi format event kalender
        $events = $loans->map(function ($loan) {
            
            $color = '';
            // Gunakan ->value untuk perbandingan yang aman
            switch ($loan->status->value) {
                case LoanStatus::APPROVED->value:
                    $color = '#3b82f6'; // Biru
                    break;
                case LoanStatus::ACTIVE->value:
                    $color = '#16a34a'; // Hijau
                    break;
                default:
                    $color = '#6b7280'; // Abu-abu
                    break;
            }
            
            $itemTitle = $loan->item ? $loan->item->brand : '[No Item]';
            $requesterName = $loan->requester ? $loan->requester->name : '[No Requester]';

            return [
                'title' => "{$itemTitle} - {$requesterName}",
                'start' => $loan->start_at,
                'end' => $loan->end_at,
                'color' => $color,
                'extendedProps' => [ 'loan_id' => $loan->id ]
            ];
        });

        return response()->json($events);
    }
}
