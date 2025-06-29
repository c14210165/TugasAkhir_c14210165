<!DOCTYPE html>
<html>
<head>
    <title>Persetujuan Barang</title>
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
            font-size: 24px;
            color: #444;
            margin-bottom: 20px;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            color: #fff;
            font-weight: bold;
            background-color: #3b82f6;
            font-size: 14px;
            text-transform: uppercase;
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
        <div class="header">Notifikasi Persetujuan Barang</div>

        <p>Halo,</p>

        <p>
            Permohonan peminjaman barang <strong>{{ $itemName }}</strong> telah 
            <span class="status-badge">Disetujui</span>.
        </p>

        <p>
            Disetujui oleh: <strong>{{ $approvedBy }}</strong><br>
            Tanggal persetujuan: <strong>{{ $approvedAt }}</strong>
        </p>

        <p>Silakan ambil barang di ruang PTIK sesuai jadwal yang telah ditentukan.</p>

        <p>Terima kasih telah menggunakan layanan peminjaman.</p>
    </div>

    <div class="footer">
        Email ini dikirim secara otomatis. Mohon untuk tidak membalas.
    </div>
</body>
</html>
