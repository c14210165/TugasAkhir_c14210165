<?php

namespace App\Models;

use App\Enums\ItemType;   // <-- Import Enum
use App\Enums\ItemStatus;
use App\Enums\LoanStatus; // <-- Import Enum
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'barcode',
        'code',
        'brand',
        'type',
        'accessories',
        'status',
    ];

    protected $casts = [
        'status' => ItemStatus::class,
    ];

    protected $appends = [
        'is_late',
        'lateness_info'
    ];

    public function getIsLateAttribute()
    {
        // Jika ada peminjaman aktif, ambil status is_late-nya.
        // Jika tidak, kembalikan false.
        // Tanda tanya ganda (??) adalah null coalescing operator.
        return $this->currentLoan?->is_late ?? false;
    }

    /**
     * [ACCESSOR BARU] Meneruskan info keterlambatan dari peminjaman aktifnya.
     */
    public function getLatenessInfoAttribute()
    {
        return $this->currentLoan?->lateness_info ?? '-';
    }

    // === RELASI DATABASE ===

    /**
     * Mendapatkan SEMUA riwayat peminjaman untuk item ini.
     */
    public function loanHistory()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Mendapatkan SATU peminjaman yang sedang aktif untuk item ini.
     */
    public function currentLoan()
    {
        // Sebuah item memiliki satu peminjaman yang sedang berlangsung
        return $this->hasOne(Loan::class)->whereIn('status', [
            LoanStatus::APPROVED->value, // Disetujui (menunggu diambil)
            LoanStatus::ACTIVE->value,   // Aktif (sedang dipinjam)
        ]);
    }
}