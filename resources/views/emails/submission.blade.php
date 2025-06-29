<!DOCTYPE html>
<html>
<head>
    <title>Permohonan Baru</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { font-size: 22px; font-weight: bold; margin-bottom: 20px; color: #111; text-align: center; }
        .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Permohonan Baru Diajukan</div>
        <p>Halo TU,</p>
        <p>Permohonan peminjaman barang <strong>{{ $itemName }}</strong> telah diajukan oleh <strong>{{ $submittedBy }}</strong>.</p>
        <p>Waktu pengajuan: <strong>{{ $submittedAt }}</strong></p>
        <p>Silakan buka sistem untuk memproses permohonan ini.</p>
    </div>
    <div class="footer">
        Email ini dikirim otomatis oleh sistem. Mohon tidak dibalas.
    </div>
</body>
</html>
