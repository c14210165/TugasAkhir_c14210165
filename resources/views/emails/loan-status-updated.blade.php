<!DOCTYPE html>
<html>
<head>
    <title>Pembaruan Status Peminjaman</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { font-size: 24px; color: #444; margin-bottom: 20px; text-align: center; }
        .status-badge { padding: 5px 10px; border-radius: 15px; color: white; font-weight: bold; }
        .status-APPROVED { background-color: #3b82f6; }
        .status-ACTIVE { background-color: #16a34a; }
        .status-REJECTED { background-color: #ef4444; }
        .status-CANCELLED { background-color: #6b7280; }
        .status-COMPLETED { background-color: #4f46e5; }
        .footer { margin-top: 20px; font-size: 12px; text-align: center; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Pembaruan Permohonan</div>
        
        <p>Halo, <strong>{{ $loan->requester->name }}</strong>,</p>
        
        <p>
            Ada pembaruan untuk permohonan peminjaman <strong>{{ $loan->item_type }}</strong> Anda.
        </p>
        
        <p>
            Status terbaru: 
            <span class="status-badge status-{{$loan->status->value}}">
                {{ str_replace('_', ' ', $loan->status->value) }}
            </span>
        </p>

        @if($loan->status === \App\Enums\LoanStatus::APPROVED)
            <p>Permohonan Anda telah disetujui! Silakan ambil barang di ruang PTIK sesuai jadwal peminjaman.</p>
        @elseif($loan->status === \App\Enums\LoanStatus::REJECTED && $loan->rejection_reason)
            <p><strong>Alasan Penolakan:</strong> {{ $loan->rejection_reason }}</p>
        @endif

        <p>Terima kasih.</p>
    </div>
    <div class="footer">
        Ini adalah email otomatis. Mohon untuk tidak membalas email ini.
    </div>
</body>
</html>