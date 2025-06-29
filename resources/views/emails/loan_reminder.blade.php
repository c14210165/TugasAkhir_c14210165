<!DOCTYPE html>
<html>
<head>
    <title>Pengingat Pengembalian Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            padding: 10px;
        }

        .container {
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .header {
            font-size: 22px;
            color: #444;
            margin-bottom: 20px;
            text-align: center;
        }

        .info {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .info strong {
            display: inline-block;
            width: 140px;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            text-align: center;
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">🔔 Pengingat Pengembalian Barang</div>

        <p>Halo <strong>{{ $loan->requester->name }}</strong>,</p>

        <p>
            Ini adalah pengingat bahwa peminjaman Anda akan segera <strong>berakhir</strong> 
            atau <strong>sudah melewati batas waktu</strong> yang ditentukan:
        </p>

        <div class="info">
            <p><strong>Barang:</strong> {{ $loan->item_type }}</p>
            <p><strong>Lokasi:</strong> {{ $loan->location }}</p>
            <p><strong>Tanggal Selesai:</strong> {{ \Carbon\Carbon::parse($loan->end_at)->format('d-m-Y H:i') }}</p>
        </div>

        <p>Mohon segera mengembalikan barang sesuai waktu yang telah ditentukan.</p>

        <p>Terima kasih atas perhatian dan kerja samanya.</p>
    </div>

    <div class="footer">
        Email ini dikirim secara otomatis oleh sistem peminjaman PTIK. Mohon untuk tidak membalas email ini.
    </div>
</body>
</html>
