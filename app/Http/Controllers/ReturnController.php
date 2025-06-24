<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Enums\ItemStatus;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        // Memulai query dengan relasi yang dibutuhkan
        // Pastikan semua relasi untuk modal detail sudah ada di sini
        $query = Loan::query()->with([
            'requester:id,name', 
            'item:id,brand,code,barcode',
            'createdBy:id,name',
            'unitApprover:id,name',
            'ptikApprover:id,name',
            'checkedInBy:id,name'
        ]);

        // [DIUBAH] Logika filter status sekarang dinamis berdasarkan request
        $allowedStatuses = [LoanStatus::ACTIVE->value, LoanStatus::COMPLETED->value];
        $status = $request->input('status');

        if ($status && in_array($status, $allowedStatuses)) {
            // Jika frontend mengirim status yang valid ('ACTIVE' atau 'COMPLETED'), gunakan itu.
            $query->where('status', $status);
        } else {
            // Jika tidak, default ke status pertama (ACTIVE) sebagai tab utama.
            $query->where('status', $allowedStatuses[0]);
        }

        // [DIUBAH] Filter 'is_late' hanya berlaku jika kita sedang melihat data 'ACTIVE'
        if ($request->input('status') === LoanStatus::ACTIVE->value) {
            if ($request->has('is_late') && $request->boolean('is_late')) {
                $query->where('is_late', true);
            }
        }

        if ($request->has('type') && $request->query('type') !== 'Semua') {
            $query->where('item_type', $request->query('type'));
        }

        // FILTER BERDASARKAN PENCARIAN (JIKA ADA)
        if ($request->has('search') && !empty($request->query('search'))) {
            $searchTerm = $request->query('search');
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->whereHas('requester', function ($requesterQuery) use ($searchTerm) {
                    $requesterQuery->where('name', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('item', function ($itemQuery) use ($searchTerm) {
                    $itemQuery->where('brand', 'like', "%{$searchTerm}%")
                            ->orWhere('code', 'like', "%{$searchTerm}%");
                });
            });
        }

        // Urutkan dan lakukan paginasi
        $query->orderBy('updated_at', 'desc');
        $loans = $query->paginate($request->query('perPage', 10));

        return response()->json($loans);
    }

    public function store(Loan $loan)
    {
        // 1. Validasi: Pastikan hanya peminjaman yang sedang AKTIF yang bisa dikembalikan.
        //    Ini penting untuk mencegah sebuah peminjaman ditandai "kembali" dua kali.
        if ($loan->status !== LoanStatus::ACTIVE) {
            return response()->json(['message' => 'Hanya peminjaman dengan status AKTIF yang bisa diproses.'], 422);
        }

        // 2. Gunakan Transaksi Database untuk menjamin keamanan dan konsistensi data
        try {
            DB::transaction(function () use ($loan) {
                // Langkah A: Update status Peminjaman menjadi COMPLETED
                $loan->update([
                    'status' => LoanStatus::COMPLETED,
                    'returned_at' => now(), // Catat waktu pengembalian
                    'checked_in_by_id' => auth()->id(),
                ]);

                // Langkah B: Update status Barang kembali menjadi AVAILABLE
                // Safety check jika karena suatu hal itemnya tidak terhubung
                if ($loan->item) {
                    $loan->item->update(['status' => ItemStatus::AVAILABLE]);
                }
            });
        } catch (\Exception $e) {
            // Tangkap jika ada error tak terduga selama transaksi
            // Anda bisa menambahkan logging di sini jika perlu: Log::error($e->getMessage());
            return response()->json(['message' => 'Terjadi error internal saat memproses pengembalian.'], 500);
        }

        // 3. Jika semua berhasil, beri response sukses
        return response()->json(['message' => 'Barang berhasil ditandai kembali. Peminjaman selesai.']);
    }

}
