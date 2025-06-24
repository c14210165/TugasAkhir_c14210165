<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Item;
use App\Models\AdditionalItemType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Enums\ItemStatus;
use App\Enums\ItemType;

class RequestController extends Controller
{
    /**
     * Tampilkan daftar permohonan.
     * Siswa hanya melihat miliknya sendiri.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Loan::query()->with(['requester:id,name', 'createdBy:id,name,role']);

        // --- LOGIKA BARU BERDASARKAN PERAN ---

        if ($user->role === UserRole::PTIK) {
            // [DIKEMBALIKAN] Logika spesifik untuk PTIK
            $query->where(function ($q) {
                // 1. Tampilkan semua yang statusnya sudah PENDING_PTIK (alur normal)
                $q->where('status', LoanStatus::PENDING_PTIK->value)
                  // 2. ATAU, tampilkan yang masih PENDING_UNIT TAPI dibuat oleh sesama PTIK (alur fast-track)
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('status', LoanStatus::PENDING_UNIT->value)
                               ->whereHas('createdBy', function ($userQuery) {
                                   $userQuery->where('role', UserRole::PTIK->value);
                               });
                  });
            });

        } elseif ($user->role === UserRole::TU) {
            // TU logic (revisi)
            $status = $request->query('status', LoanStatus::PENDING_UNIT->value);

            if ($status === LoanStatus::PENDING_UNIT->value) {
                $query->where('status', LoanStatus::PENDING_UNIT->value)
                    ->whereHas('createdBy', function ($q) {
                        $q->whereIn('role', [UserRole::TU->value, UserRole::USER->value]);
                    });
            } else {
                $query->where('created_by_id', $user->id)
                    ->where('status', $status);
            }
        } else {
            // Pengguna biasa hanya melihat permohonannya sendiri
            $query->where('requester_id', $user->id);
        }

        // --- FILTER SEKUNDER DARI TAB FRONTEND ---
        if ($request->has('type') && $request->query('type') !== 'Semua') {
            $query->where('item_type', $request->query('type'));
        }

        if ($request->has('search') && !empty($request->query('search'))) {
            $searchTerm = $request->query('search');
            $query->whereHas('requester', function ($subQuery) use ($searchTerm) {
                $subQuery->where('name', 'like', "%{$searchTerm}%");
            });
        }
        
        $query->orderBy('created_at', 'desc');
        $loans = $query->paginate($request->query('perPage', 10));

        return response()->json($loans);
    }

    public function update(Request $request, Loan $loan)
    {
        // Validasi data yang masuk dari form edit
        $validatedData = $request->validate([
            'requester_id' => 'required|exists:users,id',
            'item_type' => [
                'required',
                'string',
                // Periksa apakah nilainya ada di kolom 'name' pada tabel 'additional_item_types'
                Rule::in(AdditionalItemType::pluck('name')->toArray())
            ],
            'location'     => 'required|string|max:1000',
            'purpose'      => 'required|string|max:2000',
            'start_at'     => 'required|date',
            'end_at'       => 'required|date|after_or_equal:start_at',
        ]);

        // Lakukan update pada data loan yang ditemukan
        $loan->update($validatedData);

        // Kirim kembali response sukses beserta data yang sudah terupdate
        return response()->json([
            'message' => 'Permohonan berhasil diupdate!',
            'loan' => $loan
        ]);
    }

    public function approve(Request $request, Loan $loan)
    {
        // --- BAGIAN PENTING YANG DIPERBAIKI ---
        // Muat (load) relasi yang kita butuhkan untuk pengecekan logika.
        $loan->load('createdBy:id,role');
        // ------------------------------------

        $user = auth()->user();
        $nextStatus = null;
        $approverField = null;
        $updateData = [];

        // Kondisi 1: PTIK melakukan 'fast-track' pada permohonan timnya sendiri (yang masih PENDING_UNIT)
        $isPtkFastTrack = (
            $loan->status === LoanStatus::PENDING_UNIT &&
            $user->role === UserRole::PTIK &&
            $loan->createdBy->role === UserRole::PTIK // <-- Sekarang ini akan bekerja
        );

        if ($isPtkFastTrack) {
            // Jika fast-track, PTIK juga harus menugaskan barang
            $request->validate(['item_id' => 'required|exists:items,id']);
            
            $nextStatus = LoanStatus::APPROVED;
            $approverField = 'ptik_approver_id';
            // Set juga approver unit sebagai dirinya sendiri untuk kelengkapan data
            $updateData['unit_approver_id'] = $user->id; 
            $updateData['item_id'] = $request->input('item_id');
            $updateData['responded_at'] = now();
        } 
        // Kondisi 2: Alur normal, TU menyetujui PENDING_UNIT
        elseif ($loan->status === LoanStatus::PENDING_UNIT && $user->role === UserRole::TU) {
            $nextStatus = LoanStatus::PENDING_PTIK;
            $approverField = 'unit_approver_id';
        } 
        // Kondisi 3: Alur normal, PTIK menyetujui PENDING_PTIK
        elseif ($loan->status === LoanStatus::PENDING_PTIK && $user->role === UserRole::PTIK) {
            $request->validate(['item_id' => 'required|exists:items,id']);

            $nextStatus = LoanStatus::APPROVED;
            $approverField = 'ptik_approver_id';
            $updateData['item_id'] = $request->input('item_id');
            $updateData['responded_at'] = now();
        }

        if (is_null($nextStatus)) {
            return response()->json(['message' => 'Anda tidak memiliki hak untuk menyetujui permohonan pada tahap ini.'], 403);
        }

        // Gabungkan semua data yang akan di-update
        $updateData['status'] = $nextStatus;
        $updateData[$approverField] = $user->id;

        $loan->update($updateData);

        // Jika persetujuan final (status menjadi APPROVED), update juga status item
        if ($nextStatus === LoanStatus::APPROVED) {
            Item::find($request->input('item_id'))->update(['status' => ItemStatus::BORROWED->value]);
        }
        
        return response()->json(['message' => 'Permohonan berhasil diproses.']);
    }

    public function decline(Request $request, Loan $loan)
    {
        // Logika untuk decline tidak perlu serumit approve, karena bisa menolak di tahap mana saja.
        // Anda hanya perlu memastikan user yang login punya hak (misal: TU atau PTIK).
        $user = auth()->user();

        if (!in_array($user->role, [UserRole::TU, UserRole::PTIK])) {
            return response()->json(['message' => 'Anda tidak memiliki hak akses.'], 403);
        }
        
        $request->validate(['rejection_reason' => 'nullable|string|max:255']);
        
        $loan->update([
            'status' => LoanStatus::REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
            $user->role === UserRole::PTIK ? 'ptik_approver_id' : 'unit_approver_id' => $user->id
        ]);

        return response()->json(['message' => 'Permohonan telah ditolak.']);
    }

    /**
     * Membatalkan permohonan (hanya bisa oleh pembuatnya).
     */
    public function cancel(Loan $loan)
    {
        // Hanya user yang membuat yang bisa membatalkan, dan hanya jika masih pending
        if (auth()->id() !== $loan->created_by_id) {
            return response()->json(['message' => 'Anda tidak bisa membatalkan permohonan orang lain.'], 403);
        }
        if (!in_array($loan->status, [LoanStatus::PENDING_UNIT, LoanStatus::PENDING_PTIK])) {
            return response()->json(['message' => 'Permohonan yang sudah diproses tidak bisa dibatalkan.'], 400);
        }

        $loan->update(['status' => LoanStatus::CANCELLED]);
        
        return response()->json(['message' => 'Permohonan berhasil dibatalkan.']);
    }

}
