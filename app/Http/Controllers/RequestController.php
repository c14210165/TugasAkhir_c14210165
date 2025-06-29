<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Enums\ItemStatus;
use App\Events\LoanRequested;
use App\Events\LoanUpdated;

use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovalNotification;
use App\Mail\DeclineNotification;
use App\Mail\CancelNotification;
use App\Mail\NewForwardedLoanNotification;
use App\Notifications\LoanApprovedNotification;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LoanProcessNotification;

class RequestController extends Controller
{
    /**
     * Tampilkan daftar permohonan.
     * Siswa hanya melihat miliknya sendiri.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        $query = Loan::query()->with(['requester:id,name', 'createdBy:id,name,role', 'originalRequester.unit']);
 

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
            $status = $request->query('status'); // status bisa null

            $query->where(function ($q) use ($user) {
                $q->whereHas('requester', function ($rq) use ($user) {
                    $rq->where('unit_id', $user->unit_id); // user dari unit yang sama
                })
                ->orWhere('created_by_id', $user->id); // atau yang dibuat sendiri
            });

            // Tambah filter status jika ada
            if ($status) {
                $query->where('status', $status);
            }
        } else {
            // Pengguna biasa hanya melihat permohonannya sendiri
            $query->where('original_requester_id', $user->id);
            
            if ($request->has('status')) {
                $query->where('status', $request->query('status'));
            }
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
        $validItemTypes = ItemType::pluck('name')->toArray();
        // Validasi data yang masuk dari form edit
        $validatedData = $request->validate([
            'requester_id' => 'required|exists:users,id',
            'item_type' => [
                'required',
                'string',
                Rule::in($validItemTypes), // Validasi terhadap daftar nama tipe
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
        $loan->load(['createdBy:id,role', 'originalRequester:id,name,email,unit_id', 'requester:id,email', 'itemType']);
        $user = Auth::user();
        
        $nextStatus = null;
        $approverField = null;
        $updateData = [];

        $isPtkFastTrack = (
            $loan->status === LoanStatus::PENDING_UNIT &&
            $user->role === UserRole::PTIK &&
            $loan->createdBy->role === UserRole::PTIK
        );

        if ($isPtkFastTrack) {
            $request->validate(['item_id' => 'required|exists:items,id']);

            $nextStatus = LoanStatus::APPROVED;
            $approverField = 'ptik_approver_id';
            $updateData['unit_approver_id'] = $user->id;
            $updateData['item_id'] = $request->input('item_id');
            $updateData['responded_at'] = now();
        } 
        elseif ($loan->status === LoanStatus::PENDING_UNIT && $user->role === UserRole::TU) {
            $nextStatus = LoanStatus::PENDING_PTIK;
            $approverField = 'unit_approver_id';

            if ($loan->requester->role === UserRole::USER) {
                $updateData['requester_id'] = $user->id;
            }

            //  TU menyetujui → kirim ke semua PTIK
            $ptiks = User::where('role', UserRole::PTIK)->get();
            foreach ($ptiks as $ptik) {
                Mail::to($ptik->email)->send(new NewForwardedLoanNotification(
                    $loan->item_type,
                    $user->name,
                    now()->format('d-m-Y H:i')
                ));

                $ptik->notify(new LoanApprovedNotification(
                    "Permohonan baru diteruskan oleh $user->name untuk tipe barang {$loan->item_type}."
                ));
            }

            // TU juga kirim ke pemohon
            Mail::to($loan->originalRequester->email)->send(new ApprovalNotification(
                $loan->item_type,
                $user->name,
                now()->format('d-m-Y H:i')
            ));

            $loan->originalRequester->notify(new LoanApprovedNotification(
                "Permohonanmu telah disetujui oleh $user->name dan diteruskan ke PTIK."
            ));
        } 
        elseif ($loan->status === LoanStatus::PENDING_PTIK && $user->role === UserRole::PTIK) {
            $request->validate(['item_id' => 'required|exists:items,id']);

            $nextStatus = LoanStatus::APPROVED;
            $approverField = 'ptik_approver_id';
            $updateData['item_id'] = $request->input('item_id');
            $updateData['responded_at'] = now();

            //  PTIK menyetujui → kirim ke user
            Mail::to($loan->originalRequester->email)->send(new ApprovalNotification(
                $loan->item_type,
                $user->name,
                now()->format('d-m-Y H:i')
            ));

            $loan->originalRequester->notify(new LoanApprovedNotification(
                "Permohonanmu untuk tipe barang {$loan->item_type} telah disetujui oleh $user->name."
            ));

            //  PTIK menyetujui → kirim ke TU di unit yang sama
            $unitTus = User::where('role', UserRole::TU)
                ->where('unit_id', $loan->originalRequester->unit_id)
                ->get();

            foreach ($unitTus as $tu) {
                Mail::to($tu->email)->send(new ApprovalNotification(
                    $loan->item_type,
                    $user->name,
                    now()->format('d-m-Y H:i')
                ));

                $tu->notify(new LoanApprovedNotification(
                    "Permohonan dari {$loan->originalRequester->name} telah disetujui oleh $user->name."
                ));
            }
        }

        if (is_null($nextStatus)) {
            return response()->json(['message' => 'Anda tidak memiliki hak untuk menyetujui permohonan pada tahap ini.'], 403);
        }

        $updateData['status'] = $nextStatus;
        $updateData[$approverField] = $user->id;
        $loan->update($updateData);

        if ($nextStatus === LoanStatus::APPROVED) {
            Item::find($request->input('item_id'))->update(['status' => ItemStatus::BORROWED->value]);
        }


        return response()->json(['message' => 'Permohonan berhasil diproses.']);
    }

    public function decline(Request $request, Loan $loan)
    {
        $user = Auth::user();

        if (!in_array($user->role, [UserRole::TU, UserRole::PTIK])) {
            return response()->json(['message' => 'Anda tidak memiliki hak akses.'], 403);
        }

        $request->validate(['rejection_reason' => 'nullable|string|max:255']);

        $field = $user->role === UserRole::PTIK ? 'ptik_approver_id' : 'unit_approver_id';

        $loan->update([
            'status' => LoanStatus::REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
            $field => $user->id
        ]);

        $loan->load(['requester:id,email', 'originalRequester:id,email,unit_id', 'itemType']);
        

        // Email ke user (selalu)
        Mail::to($loan->originalRequester->email)->send(new DeclineNotification(
            $loan->item_type,
            $user->name,
            $request->input('rejection_reason'),
            now()->format('d-m-Y H:i')
        ));

        $loan->originalRequester->notify(new LoanProcessNotification(
            "Permohonanmu ditolak oleh $user->name. Alasan: " . ($request->input('rejection_reason') ?? '-')
        ));

        // Jika PTIK yang menolak → juga kirim ke TU di unit yang sama
        if ($user->role === UserRole::PTIK) {
            $unitTus = User::where('role', UserRole::TU)
                ->where('unit_id', $loan->originalRequester->unit_id)
                ->get();

            foreach ($unitTus as $tu) {
                Mail::to($tu->email)->send(new DeclineNotification(
                    $loan->item_type,
                    $user->name,
                    $request->input('rejection_reason'),
                    now()->format('d-m-Y H:i')
                ));

                $tu->notify(new LoanProcessNotification(
                    "Permohonan dari {$loan->originalRequester->name} ditolak oleh $user->name."
                ));
            }
        }

        return response()->json(['message' => 'Permohonan telah ditolak.']);
    }

    /**
     * Membatalkan permohonan (hanya bisa oleh pembuatnya).
     */
    public function cancel(Loan $loan)
    {
        $user = Auth::user();

        if ($user->id !== $loan->created_by_id) {
            return response()->json(['message' => 'Anda tidak bisa membatalkan permohonan orang lain.'], 403);
        }

        if (!in_array($loan->status, [LoanStatus::PENDING_UNIT, LoanStatus::PENDING_PTIK])) {
            return response()->json(['message' => 'Permohonan yang sudah diproses tidak bisa dibatalkan.'], 400);
        }

        $loan->update(['status' => LoanStatus::CANCELLED]);
        $loan->load(['originalRequester:id,email,unit_id', 'itemType']);


        if ($user->role === UserRole::USER) {
            $tus = User::where('role', UserRole::TU)->get();
            foreach ($tus as $tu) {
                Mail::to($tu->email)->send(new CancelNotification(
                    $loan->item_type,
                    $user->name,
                    now()->format('d-m-Y H:i')
                ));

                // Notifikasi database ke TU
                $tu->notify(new LoanProcessNotification(
                    "Permohonan dari {$user->name} dibatalkan oleh pemohon."
                ));
            }
        } 
        elseif ($user->role === UserRole::TU) {
            Mail::to($loan->originalRequester->email)->send(new CancelNotification(
                $loan->item_type,
                $user->name,
                now()->format('d-m-Y H:i')
            ));

            // Notifikasi database ke pemohon
            $loan->originalRequester->notify(new LoanProcessNotification(
                "Permohonanmu dibatalkan oleh TU: {$user->name}."
            ));
        } 
        elseif ($user->role === UserRole::PTIK) {
            Mail::to($loan->originalRequester->email)->send(new CancelNotification(
                $loan->item_type,
                $user->name,
                now()->format('d-m-Y H:i')
            ));

            $loan->originalRequester->notify(new LoanProcessNotification(
                "Permohonanmu dibatalkan oleh PTIK: {$user->name}."
            ));

            $tus = User::where('role', UserRole::TU)
                ->where('unit_id', $loan->originalRequester->unit_id)
                ->get();

            foreach ($tus as $tu) {
                Mail::to($tu->email)->send(new CancelNotification(
                    $loan->item_type,
                    $user->name,
                    now()->format('d-m-Y H:i')
                ));

                // Notifikasi database ke TU
                $tu->notify(new LoanProcessNotification(
                    "Permohonan dari {$loan->originalRequester->name} dibatalkan oleh PTIK: {$user->name}."
                ));
            }
        }

        return response()->json(['message' => 'Permohonan berhasil dibatalkan.']);
    }

}
