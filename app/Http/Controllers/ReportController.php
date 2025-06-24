<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Enums\ItemType;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function report(Request $request)
    {
        // Validasi input
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'item_type' => 'required|string',
        ]);

        $date = Carbon::parse($request->month);
        $itemType = $request->item_type;

        // --- QUERY 1: LAPORAN PER UNIT (APPROVER) ---
        // --- QUERY 1: LAPORAN PER UNIT (PEMBUAT) ---
        $unitReport = User::query()
            ->whereIn('role', [UserRole::TU, UserRole::PTIK])
            ->withCount([
                // Hitung dari 'createdLoans' (peminjaman yang DIBUAT oleh user ini)...
                'createdLoans as approved_count' => function ($query) use ($date, $itemType) {
                    $query->where('item_type', $itemType)
                          ->whereYear('created_at', $date->year)
                          ->whereMonth('created_at', $date->month)
                          // ...yang status akhirnya adalah salah satu dari ini (sukses).
                          ->where('status', LoanStatus::COMPLETED);
                },
                'createdLoans as rejected_count' => function ($query) use ($date, $itemType) {
                    $query->where('item_type', $itemType)
                          ->whereYear('created_at', $date->year)
                          ->whereMonth('created_at', $date->month)
                          // ...yang status akhirnya adalah REJECTED.
                          ->where('status', LoanStatus::REJECTED);
                },
                'createdLoans as cancelled_count' => function ($query) use ($date, $itemType) {
                    $query->where('item_type', $itemType)
                          ->whereYear('created_at', $date->year)
                          ->whereMonth('created_at', $date->month)
                          // ...yang status akhirnya adalah CANCELLED.
                          ->where('status', LoanStatus::CANCELLED);
                }
            ])
            ->get();


        // --- QUERY 2: LAPORAN PER BARANG ---
        $itemReport = Item::withTrashed()
            ->where('type', $itemType)
            ->addSelect([
                // Subquery untuk menghitung total jam (tidak berubah)
                'total_usage_hours' => Loan::query()
                    ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, borrowed_at, returned_at)) / 3600')
                    ->whereColumn('items.id', 'loans.item_id')
                    ->where('status', LoanStatus::COMPLETED)
                    ->whereYear('returned_at', $date->year)
                    ->whereMonth('returned_at', $date->month),
                    
                // [DITAMBAHKAN] Subquery untuk menghitung berapa kali dipinjam
                'loans_count' => Loan::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('items.id', 'loans.item_id')
                    ->where('status', LoanStatus::COMPLETED)
                    ->whereYear('returned_at', $date->year)
                    ->whereMonth('returned_at', $date->month)
            ])
            ->orderByDesc('total_usage_hours')
            ->get();

        return response()->json([
            'unit_report' => $unitReport,
            'item_report' => $itemReport,
        ]);
    }
}
