<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getStats()
    {
        $user = Auth::user();

        // ... query-query statistik Anda yang sudah ada ...
        $pending_requests_count = 0;
        // ...
        $total_items_count = null;
        $recent_activities = collect();

        // --- [PENAMBAHAN BARU] ---
        // Ambil peminjaman yang statusnya APPROVED dan akan dimulai dalam 7 hari ke depan
        $upcoming_loans = Loan::with(['requester:id,name', 'item:id,brand,code'])
                            ->where('status', LoanStatus::APPROVED)
                            ->whereBetween('start_at', [Carbon::now(), Carbon::now()->addDays(7)])
                            ->orderBy('start_at', 'asc')
                            ->limit(5) // Ambil 5 jadwal terdekat
                            ->get();

        // --- STATISTIK PEMINJAMAN ---

        // 1. Hitung Permohonan Masuk yang Perlu Aksi (Tergantung Role)
        $pendingRequestsQuery = Loan::query();
        if ($user->role === UserRole::PTIK) {
            $pendingRequestsQuery->where(function ($q) {
                $q->where('status', LoanStatus::PENDING_PTIK->value)
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('status', LoanStatus::PENDING_UNIT->value)
                               ->whereHas('createdBy', fn($creator) => $creator->where('role', UserRole::PTIK));
                  });
            });
        } elseif ($user->role === UserRole::TU) {
            $pendingRequestsQuery->where('status', LoanStatus::PENDING_UNIT)
                                 ->whereHas('requester', fn($q) => $q->where('unit_id', $user->unit_id));
        }
        $pending_requests_count = $pendingRequestsQuery->count();


        // 2. Hitung Peminjaman yang Sedang Aktif
        $active_loans_count = Loan::where('status', LoanStatus::ACTIVE)->count();

        // 3. Hitung Peminjaman yang Terlambat (status AKTIF tapi sudah lewat tenggat)
        $overdue_loans_count = Loan::where('status', LoanStatus::ACTIVE)
                                   ->where('end_at', '<', now())
                                   ->count();

        // --- STATISTIK BARANG ---
        $available_items_count = Item::where('status', 'AVAILABLE')->count();
        $total_items_count = Item::count();

        // --- AKTIVITAS TERBARU ---
        $recent_activities = Loan::with(['requester:id,name', 'item:id,brand,code'])
                                ->latest('updated_at') // Ambil yang paling baru di-update
                                ->limit(5)
                                ->get();
        
        // Kembalikan semua data dalam satu response JSON
        return response()->json([
            'pending_requests' => $pending_requests_count,
            'active_loans' => $active_loans_count,
            'overdue_loans' => $overdue_loans_count,
            'available_items' => $available_items_count,
            'total_items' => $total_items_count,
            'recent_activities' => $recent_activities,
            'upcoming_loans' => $upcoming_loans,
        ]);
    }
}
