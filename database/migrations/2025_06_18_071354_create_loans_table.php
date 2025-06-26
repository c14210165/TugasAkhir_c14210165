<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignId('requester_id')->constrained('users'); // User yang mengajukan
            $table->foreignId('original_requester_id')->constrained('users');
            $table->string('item_type'); // Tipe barang yang diminta
            $table->text('location'); // Lokasi penggunaan
            $table->text('purpose'); // Keperluan peminjaman
            $table->dateTime('start_at'); // Rencana tanggal mulai pinjam
            $table->dateTime('end_at'); // Rencana tanggal selesai pinjam

            // === INFORMASI PERSETUJUAN (Dari status 'requests' & logika proses) ===
            $table->foreignId('unit_approver_id')->nullable()->constrained('users'); // Disetujui oleh Kepala Unit
            $table->foreignId('ptik_approver_id')->nullable()->constrained('users'); // Disetujui oleh PTIK
            $table->text('rejection_reason')->nullable(); // Alasan jika ditolak

            // === INFORMASI PEMINJAMAN (Dari tabel loans) ===
            $table->foreignId('item_id')->nullable()->constrained('items'); // Barang spesifik yang dipinjamkan
            $table->foreignId('checked_out_by_id')->nullable()->constrained('users');
            $table->boolean('is_late')->default(0);

            // === INFORMASI PENGEMBALIAN (Dari tabel returns) ===
            $table->foreignId('checked_in_by_id')->nullable()->constrained('users');
            $table->string('return_condition')->nullable();
            $table->text('return_notes')->nullable();
            $table->decimal('fine', 12, 2)->default(0.00); // Denda

            $table->string('status')->default('PENDING_UNIT');
            
            $table->dateTime('responded_at')->nullable(); // Kapan final approval diberikan
            $table->dateTime('borrowed_at')->nullable(); // Kapan barang diambil peminjam
            $table->dateTime('returned_at')->nullable(); // Kapan barang dikembalikan

            $table->timestamps(); // created_at (sebagai tanggal permintaan) & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
