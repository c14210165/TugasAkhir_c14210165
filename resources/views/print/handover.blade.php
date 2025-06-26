<!DOCTYPE html>
<html>
<head>
    <title>Form Barang Keluar - {{ $loan->item->code }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: sans-serif;
        }
        .form-container {
            page-break-inside: avoid;
            margin-bottom: 2rem;
        }
        @media print {
            @page {
                margin: 0.5in;
            }
        }
    </style>
</head>
<body class="text-xs bg-white" onload="window.print(); setTimeout(() => window.close(), 1000);">

    @for ($i = 0; $i < 2; $i++)
    <div class="form-container">
        <div class="flex items-center pb-2 mb-2">
            <div class="w-20 h-20 flex-shrink-0 flex items-center justify-center mr-4">
                <img src="{{ asset('images/logo.png') }}" alt="Logo Universitas" class="object-contain h-full w-full" onerror="this.style.display='none'; this.parentElement.innerHTML='<p class=\'text-gray-400 text-xs text-center\'>Logo</p>';">
            </div>
            <div class="w-full text-center border-b-4 border-black">
                <h1 class="text-lg font-bold">FORM BARANG KELUAR/PEMINJAMAN</h1>
                <h2 class="text-md font-semibold">PUSAT TEKNOLOGI INFORMASI DAN KOMUNIKASI</h2>
            </div>
        </div>

        <table class="w-full border-collapse border border-black text-xs mb-4">
            <thead>
                <tr class="bg-gray-200 text-center font-bold">
                    <th class="border border-black px-2 py-1">No. Dokumen</th>
                    <th class="border border-black px-2 py-1">No. Revisi</th>
                    <th class="border border-black px-2 py-1">Tanggal Berlaku</th>
                    <th class="border border-black px-2 py-1">Halaman</th>
                </tr>
            </thead>
            <tbody>
                <tr class="text-center">
                    <td class="border border-black px-2 py-1">F02-PM.01-PTIK-UKP</td>
                    <td class="border border-black px-2 py-1">00</td>
                    <td class="border border-black px-2 py-1">15-05-2020</td>
                    <td class="border border-black px-2 py-1">1 dari 1</td>
                </tr>
            </tbody>
        </table>

        <table class="w-full text-xs mb-4">
            <tr>
                <td class="w-1/2 py-1 align-top"><strong>Tgl. Ambil:</strong> {{ \Carbon\Carbon::parse($loan->borrowed_at)->format('d-m-Y') }}</td>
                <td class="w-1/2 py-1 align-top"><strong>Peminjam:</strong> {{ $loan->requester->name }}</td>
            </tr>
            <tr>
                <td class="w-1/2 py-1 align-top"><strong>Jadwal Pinjam:</strong> {{ \Carbon\Carbon::parse($loan->start_at)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($loan->end_at)->format('d-m-Y') }}</td>
                <td class="w-1/2 py-1 align-top"><strong>Keperluan:</strong> {{ $loan->purpose }}</td>
            </tr>
        </table>

        <table class="w-full border-collapse border border-black text-xs">
            <thead>
                <tr class="bg-gray-200 font-bold text-center">
                    <th class="border border-black px-2 py-1">No Order</th>
                    <th class="border border-black px-2 py-1">Kode Barang</th>
                    <th class="border border-black px-2 py-1">No Laptop</th>
                    <th class="border border-black px-2 py-1">Merk Barang</th>
                    <th class="border border-black px-2 py-1 w-2/5">Kelengkapan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-black px-2 py-1 text-center">{{ $loan->id }}</td>
                    <td class="border border-black px-2 py-1 text-center">{{ $loan->item->barcode }}</td>
                    <td class="border border-black px-2 py-1 text-center">{{ $loan->item->code ?? 'N/A' }}</td>
                    <td class="border border-black px-2 py-1">{{ $loan->item->brand }}</td>
                    <td class="border border-black px-2 py-1">{{ $loan->item->accessories ?? '-' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-8 flex justify-between text-xs text-center">
            <div>
                <p>Mengetahui,</p>
                <div class="mt-16">
                    <p>({{ $loan->checkedOutBy->name ?? '.....................' }})</p>
                </div>
            </div>
            <div>
                <p>Peminjam,</p>
                <div class="mt-16">
                    <p>({{ $loan->requester->name ?? '.....................' }})</p>
                </div>
            </div>
        </div>
    </div>
    @endfor

</body>
</html>
