<?php

namespace App\Models;

use App\Enums\ItemType;     // <-- Import Enum
use App\Enums\LoanStatus;   // <-- Import Enum
use App\Enums\ReturnCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory;

    // Karena tabel ini punya banyak kolom, lebih mudah menggunakan guarded
    // Ini berarti semua kolom boleh diisi, KECUALI 'id'
    protected $guarded = ['id'];

    protected $casts = [
        // Mengubah string tanggal menjadi objek Carbon yang powerful
        'start_at'      => 'datetime',
        'end_at'        => 'datetime',
        'responded_at'   => 'datetime',
        'borrowed_at'   => 'datetime',
        'returned_at'   => 'datetime',

        // Mengubah angka 0/1 menjadi nilai boolean true/false
        'is_late'       => 'boolean',

        // Mengubah string status & tipe menjadi objek Enum
        'status'        => LoanStatus::class,

        // Mengubah angka desimal
        'fine'          => 'decimal:2',

        'is_late'       => 'boolean'
    ];

    protected $appends = [
        'is_late', 
        'lateness_info'
    ];

    public function getIsLateAttribute(): bool
    {
        // Peminjaman tidak bisa dianggap terlambat jika tidak punya tenggat waktu.
        if (!$this->end_at) {
            return false;
        }

        // KASUS 1: Peminjaman sudah selesai (status COMPLETED)
        // Cek apakah tanggal pengembalian aktual melewati tenggat.
        if ($this->status === LoanStatus::COMPLETED && $this->returned_at) {
            return $this->returned_at > $this->end_at;
        }

        // KASUS 2: Peminjaman masih aktif (status ACTIVE)
        // Cek apakah hari ini sudah melewati tenggat.
        if ($this->status === LoanStatus::ACTIVE) {
            return $this->end_at->isPast();
        }

        // Untuk status lain (PENDING, CANCELLED, dll), tidak dianggap terlambat.
        return false;
    }

    /**
     * [ACCESSOR BARU] Untuk membuat teks keterangan keterlambatan.
     * Akan bisa diakses melalui $loan->lateness_info
     */
    public function getLatenessInfoAttribute(): string
    {
        // Gunakan accessor is_late yang sudah ada
        if (!$this->is_late) {
            return '-';
        }

        // Tentukan tanggal pembanding: tanggal kembali aktual, atau hari ini jika belum kembali
        $comparisonDate = $this->returned_at ? Carbon::parse($this->returned_at) : now();
        $dueDate = Carbon::parse($this->end_at);

        // Gunakan Carbon untuk menghitung selisih waktu dengan detail
        $diff = $comparisonDate->diff($dueDate);

        $parts = [];
        if ($diff->m > 0) {
            $parts[] = "{$diff->m} bulan";
        }
        if ($diff->d > 0) {
            $parts[] = "{$diff->d} hari";
        }
        if ($diff->h > 0) {
            $parts[] = "{$diff->h} jam";
        }
        
        // Jika selisihnya sangat kecil (kurang dari 1 jam)
        if (empty($parts)) {
            return 'Terlambat < 1 jam';
        }

        return 'Terlambat ' . implode(', ', $parts);
    }

    // === RELASI DATABASE ===

    /**
     * Relasi ke user yang membuat permohonan di sistem.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relasi ke user yang akan meminjam/menggunakan barang.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Relasi ke barang spesifik yang dipinjamkan.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id')->withTrashed();
    }

    /**
     * Relasi ke user yang menyetujui di level Unit.
     */
    public function unitApprover()
    {
        return $this->belongsTo(User::class, 'unit_approver_id');
    }

    /**
     * Relasi ke user yang menyetujui di level PTIK.
     */
    public function ptikApprover()
    {
        return $this->belongsTo(User::class, 'ptik_approver_id');
    }

    /**
     * Relasi ke petugas yang menyerahkan barang.
     */
    public function checkedOutBy()
    {
        return $this->belongsTo(User::class, 'checked_out_by_id');
    }

    /**
     * Relasi ke petugas yang menerima barang kembali.
     */
    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by_id');
    }
}