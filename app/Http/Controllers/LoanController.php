<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Item;
use App\Models\User;
use App\Models\ItemType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Enums\ItemStatus;
use App\Enums\LoanStatus;
use App\Enums\UserRole;

use App\Mail\NewSubmissionNotification;
use Illuminate\Support\Facades\Mail;
use App\Notifications\LoanSubmittedNotification;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user(); // Ambil data user yang sedang login

        // Memulai query dengan semua relasi yang dibutuhkan
        $query = Loan::query()->with([
            'requester:id,name', 
            'item:id,brand,code,barcode', 
            'createdBy:id,name', 
            'checkedOutBy:id,name'
        ]);

        if ($user->role === UserRole::PTIK) {
            
            // --- LOGIKA UNTUK ADMIN PTIK ---
            $allowedStatuses = [
                LoanStatus::APPROVED->value, LoanStatus::ACTIVE->value,
                LoanStatus::REJECTED->value, LoanStatus::CANCELLED->value,
                LoanStatus::COMPLETED->value,
            ];
            // Logika default atau filter berdasarkan tab
            $statusFilter = $request->query('status');
            if ($statusFilter && $statusFilter !== 'Semua' && in_array($statusFilter, $allowedStatuses)) {
                $query->where('status', $statusFilter);
            } else {
                $query->whereIn('status', $allowedStatuses);
            }

        } elseif ($user->role === UserRole::TU) {
            
            // --- LOGIKA KHUSUS UNTUK STAF TU ---
            $query->where('created_by_id', $user->id);
            $allowedTuStatuses = [LoanStatus::ACTIVE->value, LoanStatus::COMPLETED->value];
            $statusFilter = $request->query('status', LoanStatus::ACTIVE->value);
            if (in_array($statusFilter, $allowedTuStatuses)) {
                $query->where('status', $statusFilter);
            } else {
                $query->where('status', LoanStatus::ACTIVE->value);
            }

        } else { // Ini adalah blok untuk USER BIASA
            
            // --- [DITAMBAHKAN] LOGIKA KHUSUS UNTUK USER BIASA ---
            // 1. Hanya tampilkan peminjaman yang pemohonnya adalah dia sendiri
            $query->where('requester_id', $user->id);

            // 2. Filter berdasarkan status dari tab frontend
            if ($request->has('status')) {
                $query->where('status', $request->query('status'));
            }
        }

        // --- Filter sekunder (tipe & search) berlaku untuk semua peran ---
        if ($request->has('type') && $request->query('type') !== 'Semua') {
            $query->where('item_type', $request->query('type'));
        }
        if ($request->has('search') && !empty($request->query('search'))) {
            // ... logika search Anda ...
        }
        
        $query->orderBy('updated_at', 'desc');
        $loans = $query->paginate($request->query('perPage', 10));

        return response()->json($loans);
    }

    public function show(Loan $loan)
    {
        // Eager load semua relasi yang dibutuhkan oleh modal detail
        $loan->load([
            'requester', 
            'item', 
            'createdBy', 
            'unitApprover', 
            'ptikApprover', 
            'checkedOutBy', 
            'checkedInBy'
        ]);

        return response()->json($loan);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'requester_id' => 'required|exists:users,id', 
            'original_requester_id' => 'required|exists:users,id',
            'item_type' => 'required',
            'location' => 'required|string|max:1000',
            'purpose' => 'required|string|max:2000',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
        ]);

        $user = Auth::user();
        $validatedData['created_by_id'] = $user->id;
        $validatedData['status'] = LoanStatus::PENDING_UNIT->value;

        $loan = Loan::create($validatedData);
        $loan->load(['requester:id,name,email,unit_id', 'originalRequester:id,name,email,unit_id', 'createdBy:id,name,role', 'itemType']);

        // === Pengiriman email disesuaikan dengan peran ===
        if ($user->role === UserRole::USER) {
            // USER → kirim ke semua TU
            $unitTus = User::where('role', UserRole::TU)
                ->where('unit_id', $loan->originalRequester->unit_id)
                ->get();

            foreach ($unitTus as $tu) {
                Mail::to($tu->email)->send(new NewSubmissionNotification(
                    $loan->item_type,
                    $user->name,
                    now()->format('d-m-Y H:i')
                ));

                $tu->notify(new LoanSubmittedNotification(
                    $loan->item_type,
                    $user->name
                ));
            }
        }
        elseif ($user->role === UserRole::TU) {
            $target = $loan->originalRequester;
            
            // TU → buat permohonan untuk orang lain → kirim ke user
            Mail::to($loan->originalRequester->email)->send(new NewSubmissionNotification(
                $loan->item_type,
                $user->name,
                now()->format('d-m-Y H:i')
            ));

            $target->notify(new LoanSubmittedNotification(
                $loan->item_type,
                $user->name
            ));
        }
        elseif ($user->role === UserRole::PTIK) {
            $target = $loan->originalRequester;

            // PTIK → kirim ke user dan TU yang satu unit
            Mail::to($loan->originalRequester->email)->send(new NewSubmissionNotification(
                $loan->item_type,
                $user->name,
                now()->format('d-m-Y H:i')
            ));

            $target->notify(new LoanSubmittedNotification(
                $loan->item_type,
                $user->name
            ));


            $unitTus = User::where('role', UserRole::TU)
                ->where('unit_id', $loan->originalRequester->unit_id)
                ->get();

            foreach ($unitTus as $tu) {
                Mail::to($tu->email)->send(new NewSubmissionNotification(
                    $loan->item_type,
                    $user->name,
                    now()->format('d-m-Y H:i')
                ));

                $tu->notify(new LoanSubmittedNotification(
                    $loan->item_type,
                    $user->name
                ));
            }
        }

        return response()->json([
            'message' => 'Permohonan berhasil diajukan!',
            'loan' => $loan
        ], 201);
    }

    /**
     * [BARU] Mengubah status peminjaman menjadi ACTIVE (barang diserahkan).
     */
    public function checkout(Loan $loan)
    {
        // Hanya peminjaman yang sudah disetujui yang bisa diserahkan
        if ($loan->status !== LoanStatus::APPROVED) {
            return response()->json(['message' => 'Hanya peminjaman dengan status APPROVED yang bisa diserahkan.'], 403);
        }

        // Gunakan transaksi untuk memastikan data konsisten
        DB::transaction(function () use ($loan) {
            // Update status peminjaman menjadi ACTIVE
            $loan->update([
                'status' => LoanStatus::ACTIVE,
                'borrowed_at' => now(), // Catat waktu barang diambil
                'checked_out_by_id' => Auth::id(),
            ]);

            // Update status item menjadi BORROWED
            $loan->item->update(['status' => ItemStatus::BORROWED]);
        });

        $loan->load(['requester', 'item', 'checkedOutBy', 'createdBy']);

        return response()->json(['message' => 'Barang berhasil diserahkan. Status peminjaman kini AKTIF.', 'loan' => $loan]);
    }

    /**
     * [BARU] Mengupdate detail peminjaman.
     */
    public function update(Request $request, Loan $loan)
    {
        // Peminjaman hanya bisa diedit jika statusnya APPROVED atau ACTIVE
        if (!in_array($loan->status, [LoanStatus::APPROVED, LoanStatus::ACTIVE])) {
            return response()->json(['message' => 'Peminjaman dengan status ini tidak dapat diedit lagi.'], 403);
        }

        // Validasi data yang masuk, termasuk item_id yang baru
        $validatedData = $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'purpose' => 'required|string|max:1000',
            'location' => 'required|string|max:255',
            'item_id' => 'required|exists:items,id', // Validasi item_id baru
        ]);

        $originalItemId = $loan->item_id;
        $newItemId = $validatedData['item_id'];

        // Cek apakah ada pergantian barang
        if ($originalItemId == $newItemId) {
            // --- TIDAK ADA PERGANTIAN BARANG ---
            // Cukup update detail peminjaman saja. Transaksi tidak wajib.
            unset($validatedData['item_id']); // Hapus item_id dari data update
            $loan->update($validatedData);
        } else {
            // --- ADA PERGANTIAN BARANG ---
            // Wajib pakai transaksi untuk menukar barang dengan aman
            try {
                DB::transaction(function () use ($loan, $originalItemId, $newItemId, $validatedData) {
                    // 1. Kunci dan validasi item BARU
                    $newItem = Item::lockForUpdate()->find($newItemId);
                    if ($newItem->status !== ItemStatus::AVAILABLE) {
                        // Jika item baru ternyata sudah dipinjam orang lain, gagalkan.
                        throw ValidationException::withMessages([
                           'item_id' => 'Barang pengganti yang dipilih sudah tidak tersedia.'
                        ]);
                    }

                    // 2. Bebaskan item LAMA
                    if ($originalItemId) {
                        Item::find($originalItemId)->update(['status' => ItemStatus::AVAILABLE]);
                    }

                    // 3. Ambil (reserve) item BARU
                    $newItem->update(['status' => ItemStatus::BORROWED]);
                    
                    // 4. Update data peminjaman dengan detail & item_id baru
                    $loan->update($validatedData);
                });
            } catch (ValidationException $e) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }
        }

        return response()->json([
            'message' => 'Data peminjaman berhasil diupdate.',
            'loan' => $loan->fresh()->load(['requester:id,name', 'item:id,brand,code']),
        ]);
    }

    /**
     * [BARU] Membatalkan peminjaman.
     */
    public function cancel(Loan $loan)
    {
        // Peminjaman hanya bisa dibatalkan jika statusnya APPROVED atau ACTIVE
        if (!in_array($loan->status, [LoanStatus::APPROVED, LoanStatus::ACTIVE])) {
            return response()->json(['message' => 'Peminjaman dengan status ini tidak dapat dibatalkan.'], 403);
        }

        // Gunakan transaksi untuk memastikan data konsisten
        DB::transaction(function () use ($loan) {
            // Ubah status peminjaman menjadi CANCELLED
            $loan->update(['status' => LoanStatus::CANCELLED]);

            // Jika ada item yang terhubung, kembalikan statusnya menjadi AVAILABLE
            if ($loan->item) {
                $loan->item->update(['status' => ItemStatus::AVAILABLE]);
            }
        });

        return response()->json(['message' => 'Peminjaman berhasil dibatalkan.']);
    }
}
