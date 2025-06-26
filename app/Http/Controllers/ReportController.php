<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
// Hapus Enum yang tidak lagi digunakan jika ada
// use App\Enums\ItemType; 
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Menghasilkan laporan peminjaman berdasarkan bulan dan tipe barang.
     * Tipe barang sekarang diterima sebagai ID dari tabel item_types.
     */
    public function report(Request $request)
    {
        // --- VALIDASI INPUT (TIDAK BERUBAH) ---
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'item_type' => 'required|integer|exists:item_types,id', // Validasi terhadap tabel item_types
        ]);

        $date = Carbon::parse($request->month);
        $itemTypeId = $request->item_type;

        // --- QUERY 1: LAPORAN PER UNIT (PEMBUAT) ---
        // Query diubah untuk menggunakan whereHas
        $unitReport = User::query()
            ->whereIn('role', [UserRole::TU, UserRole::PTIK])
            ->withCount([
                // --- PERBAIKAN: Menggunakan whereHas untuk filter via relasi 'item' ---
                'createdLoans as approved_count' => function ($query) use ($date, $itemTypeId) {
                    $query->whereHas('item', function ($itemQuery) use ($itemTypeId) {
                        $itemQuery->where('item_type_id', $itemTypeId);
                    })
                          ->whereYear('created_at', $date->year)
                          ->whereMonth('created_at', $date->month)
                          ->where('status', LoanStatus::COMPLETED);
                },
                'createdLoans as rejected_count' => function ($query) use ($date, $itemTypeId) {
                    $query->whereHas('item', function ($itemQuery) use ($itemTypeId) {
                        $itemQuery->where('item_type_id', $itemTypeId);
                    })
                          ->whereYear('created_at', $date->year)
                          ->whereMonth('created_at', $date->month)
                          ->where('status', LoanStatus::REJECTED);
                },
                'createdLoans as cancelled_count' => function ($query) use ($date, $itemTypeId) {
                    $query->whereHas('item', function ($itemQuery) use ($itemTypeId) {
                        $itemQuery->where('item_type_id', $itemTypeId);
                    })
                          ->whereYear('created_at', $date->year)
                          ->whereMonth('created_at', $date->month)
                          ->where('status', LoanStatus::CANCELLED);
                }
            ])
            ->get();


        // --- QUERY 2: LAPORAN PER BARANG (TIDAK BERUBAH) ---
        // Query ini sudah benar karena filter langsung di model Item.
        $itemReport = Item::withTrashed()
            ->where('item_type_id', $itemTypeId)
            ->addSelect([
                'total_usage_hours' => Loan::query()
                    ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, borrowed_at, returned_at)) / 3600')
                    ->whereColumn('items.id', 'loans.item_id')
                    ->where('status', LoanStatus::COMPLETED)
                    ->whereYear('returned_at', $date->year)
                    ->whereMonth('returned_at', $date->month),
                    
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