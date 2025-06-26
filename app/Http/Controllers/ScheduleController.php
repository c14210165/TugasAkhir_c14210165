<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Enums\LoanStatus;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        // Memulai query dasar, termasuk relasi
        $query = Loan::query()
            ->with(['requester:id,name', 'item:id,brand,code', 'unitApprover:id,name'])
            ->whereIn('status', [LoanStatus::APPROVED, LoanStatus::ACTIVE])
            ->whereNotNull('item_id'); // Hanya peminjaman yang sudah punya barang

        // Filter tipe barang jika ada dari frontend
        if ($request->has('type') && $request->query('type') !== 'Semua') {
            $query->where('item_type', $request->query('type'));
        }

        $loans = $query->get();

        // Mapping menjadi format event untuk FullCalendar
        $events = $loans->map(function ($loan) {
            $color = match ($loan->status->value) {
                LoanStatus::APPROVED->value => '#3b82f6', // Biru
                LoanStatus::ACTIVE->value => '#16a34a',   // Hijau
                default => '#6b7280',                      // Abu-abu
            };

            return [
                'title' => ($loan->item->brand ?? '[No Item]') . ' - ' . ($loan->requester->name ?? '[No Requester]'),
                'start' => $loan->start_at,
                'end' => $loan->end_at,
                'color' => $color,
                'extendedProps' => [
                    'loan_id' => $loan->id,
                    'item_code' => $loan->item?->code ?? '-',
                    'requester_name' => $loan->unitApprover?->name ?? '-',
                    'purpose' => $loan->purpose ?? '-',
                    'returned_at' => $loan->returned_at,
                ]
            ];
        });

        return response()->json($events);
    }
}
