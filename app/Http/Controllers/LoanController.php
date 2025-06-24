<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Item;
use App\Models\AdditionalItemType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Enums\ItemType;
use App\Enums\ItemStatus;
use App\Enums\LoanStatus;
use App\Enums\UserRole;

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

        // --- LOGIKA UTAMA BERDASARKAN PERAN ---
        if ($user->role === UserRole::TU) {
            
            // --- LOGIKA KHUSUS UNTUK STAF TU ---
            // 1. HANYA tampilkan data yang DIBUAT oleh TU yang login
            $query->where('created_by_id', $user->id);

            // [DIPERBAIKI] Logika filter status sekarang sepenuhnya patuh pada frontend
            $allowedTuStatuses = [LoanStatus::ACTIVE->value, LoanStatus::COMPLETED->value];
            // Ambil filter status dari request, dengan default 'ACTIVE'
            $statusFilter = $request->query('status', LoanStatus::ACTIVE->value);

            // Pastikan status yang diminta valid untuk TU, lalu terapkan
            if (in_array($statusFilter, $allowedTuStatuses)) {
                $query->where('status', $statusFilter);
            } else {
                // Jika frontend mengirim status yang tidak diizinkan, default ke ACTIVE
                $query->where('status', LoanStatus::ACTIVE->value);
            }

        } else { // Asumsi selain TU adalah PTIK atau admin lain yang bisa melihat semua

            // --- LOGIKA UNTUK ADMIN PTIK (TIDAK BERUBAH DARI KODE ANDA) ---
            $allowedStatuses = [
                LoanStatus::APPROVED->value,
                LoanStatus::ACTIVE->value,
                LoanStatus::REJECTED->value,
                LoanStatus::CANCELLED->value,
                LoanStatus::COMPLETED->value,
            ];

            if ($request->has('status') && $request->query('status') !== 'Semua') {
                if (in_array($request->query('status'), $allowedStatuses)) {
                    $query->where('status', $request->query('status'));
                }
            } else {
                // Jika PTIK memilih tab 'Semua', tampilkan semua status yang diizinkan
                $query->whereIn('status', $allowedStatuses);
            }
        }

        if ($request->has('type') && $request->query('type') !== 'Semua') {
            $query->where('item_type', $request->query('type'));
        }

        // 2. Filter berdasarkan Pencarian
        if ($request->has('search') && !empty($request->query('search'))) {
            $searchTerm = $request->query('search');
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->whereHas('requester', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                })->orWhereHas('item', function ($q) use ($searchTerm) {
                    $q->where('code', 'like', "%{$searchTerm}%")
                      ->orWhere('brand', 'like', "%{$searchTerm}%");
                });
            });
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
        $enumTypes = array_column(ItemType::cases(), 'value');
        // 2. Ambil dari Database
        $dbTypes = AdditionalItemType::pluck('name')->toArray();
        // 3. Gabungkan keduanya menjadi satu array
        $allValidTypes = array_merge($enumTypes, $dbTypes);

        // 1. Validasi semua input yang dikirim dari form Vue
        $validatedData = $request->validate([
            // 'exists:users,id' memastikan user yang dipilih benar-benar ada di database
            'requester_id' => 'required|exists:users,id', 
            
            // Validasi tipe barang harus salah satu dari yang ada di Enum
            'item_type' => [
                'required',
                'string',
                // Periksa apakah nilainya ada di kolom 'name' pada tabel 'additional_item_types'
                Rule::in(AdditionalItemType::pluck('name')->toArray())
            ],

            'location' => 'required|string|max:1000',
            'purpose' => 'required|string|max:2000',
            
            // Validasi tanggal & waktu
            'start_at' => 'required|date',
            'end_at'   => 'required|date|after_or_equal:start_at', // Tgl selesai harus setelah atau sama dengan tgl mulai
        ]);

        // 2. Tambahkan data yang di-generate oleh server, bukan dari input user
        $validatedData['created_by_id'] = Auth::id(); // Ambil ID user yang sedang login
        $validatedData['status'] = LoanStatus::PENDING_UNIT->value; // Set status awal

        // 3. Simpan data yang sudah tervalidasi dan lengkap ke database
        $loan = Loan::create($validatedData);

        $loan->load(['requester:id,name', 'createdBy:id,name,role']);

        // 4. Kirim kembali response sukses beserta data yang baru dibuat
        return response()->json([
            'message' => 'Permohonan berhasil diajukan!',
            'loan' => $loan // Muat relasi agar bisa langsung ditampilkan jika perlu
        ], 201); // 201 Created adalah status code standar untuk POST yang sukses
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
                'checked_out_by_id' => auth()->id(),
            ]);

            // Update status item menjadi BORROWED
            $loan->item->update(['status' => ItemStatus::BORROWED]);
        });

        return response()->json(['message' => 'Barang berhasil diserahkan. Status peminjaman kini AKTIF.']);
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
