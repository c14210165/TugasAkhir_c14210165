<!DOCTYPE html>
<html>
<head>
    <title>Penolakan Permohonan</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { font-size: 24px; color: #d14343; margin-bottom: 20px; text-align: center; }
        .badge-reject { background-color: #ef4444; color: white; padding: 5px 10px; border-radius: 5px; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Permohonan Ditolak</div>

        <p>Halo,</p>

        <p>
            Permohonan peminjaman barang <strong>{{ $itemName }}</strong> telah 
            <span class="badge-reject">DITOLAK</span> oleh <strong>{{ $rejectedBy }}</strong>.
        </p>

        @if($rejectionReason)
            <p><strong>Alasan Penolakan:</strong> {{ $rejectionReason }}</p>
        @endif

        <p>Waktu penolakan: <strong>{{ $rejectedAt }}</strong></p>

        <p>Silakan ajukan kembali jika diperlukan.</p>
    </div>

    <div class="footer">
        Email ini dikirim otomatis oleh sistem. Mohon tidak dibalas.
    </div>
</body>
</html>
