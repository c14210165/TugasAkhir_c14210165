<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\AdditionalItemType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Enums\ItemType; // <-- Import Enum Tipe Barang
use App\Enums\ItemStatus;
use App\Enums\LoanStatus; // <-- Import Enum Status Barang

class ItemController extends Controller
{
    // Mengambil semua Item
    public function index(Request $request)
    {
        // Memulai query builder agar kita bisa menambahkan kondisi secara dinamis
        $query = Item::query()->with([
            'currentLoan.requester:id,name', // Ambil nama dari peminjam (requester)
            'currentLoan.unitApprover:id,name' // Ambil nama dari penyetuju unit
        ]);

        // --- FILTERING ---
        // Cek jika ada parameter 'type' di URL (cth: /api/items?type=Laptop)
        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        // Cek jika ada parameter 'status' di URL (cth: /api/items?status=AVAILABLE)
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        // --- SEARCHING ---
        // Cek jika ada parameter 'search' di URL (cth: /api/items?search=Dell)
        if ($request->has('search')) {
            $searchTerm = $request->query('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('brand', 'like', "%{$searchTerm}%")
                  ->orWhere('code', 'like', "%{$searchTerm}%")
                  ->orWhere('barcode', 'like', "%{$searchTerm}%");
            });
        }

        // --- SORTING ---
        // Cek jika ada parameter 'sortBy' dan 'sortDirection'
        // Default sort: dari yang terbaru (created_at, descending)
        $sortBy = $request->query('sortBy', 'created_at');
        $sortDirection = $request->query('sortDirection', 'desc');
        $query->orderBy($sortBy, $sortDirection);


        // --- PAGINATION ---
        // Ambil hasil query dengan pagination
        // Default 10 item per halaman, bisa diubah via parameter ?perPage=N
        $perPage = $request->query('perPage', 10);
        $items = $query->paginate($perPage);

        // Kembalikan hasil sebagai response JSON
        return response()->json($items);
    }

    public function getTypes()
    {
        // Sekarang HANYA mengambil dari database
        $db_types = AdditionalItemType::query()
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($type) {
                return [
                    'value' => $type->name,
                    'label' => $type->name // Dibuat sama agar konsisten
                ];
            });

        return response()->json($db_types);
    }

    public function getAvailable(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        $items = Item::query()
            ->where('type', $request->query('type'))
            ->where('status', 'AVAILABLE') // Hanya yang statusnya AVAILABLE
            ->select('id', 'brand', 'code') // Ambil data yang perlu saja
            ->get();
        
        return response()->json($items);
    }

    // Menyimpan Item baru
    public function store(Request $request)
    {
        // Langkah 1: Validasi semua input dari form
        $validatedData = $request->validate([
            'brand'       => 'required|string|max:255',
            'code'        => 'required|string|max:255|unique:items,code', // Pastikan kode unik
            'barcode'     => 'required|string|max:255|unique:items,barcode', // Pastikan barcode unik
            'type'        => ['required', 'string', 'exists:additional_item_types,name'], // Validasi berdasarkan Enum
            'accessories' => 'nullable|string',
            'status'      => ['required', Rule::in(array_column(ItemStatus::cases(), 'value'))], // Validasi berdasarkan Enum
        ]);

        // Langkah 2: Jika validasi berhasil, buat item baru
        $item = Item::create($validatedData);

        // Langkah 3: Kembalikan response sukses beserta data item yang baru dibuat
        return response()->json([
            'message' => 'Barang berhasil ditambahkan!',
            'item' => $item
        ], 201); // 201 Created adalah status code yang tepat untuk POST sukses
    }

    /**
     * Mengupdate data barang yang sudah ada.
     * Laravel secara otomatis menemukan $item berdasarkan {id} di URL (Route Model Binding)
     */
    public function update(Request $request, Item $item)
    {
        // Validasi input untuk proses update
        $validatedData = $request->validate([
            'brand'       => 'required|string|max:255',
            // Pastikan kode unik, TAPI abaikan untuk item ID saat ini
            'code'        => ['required', 'string', 'max:255', Rule::unique('items')->ignore($item->id)],
            'barcode'     => ['required', 'string', 'max:255', Rule::unique('items')->ignore($item->id)],
            'type'        => ['required', 'string', 'exists:additional_item_types,name'],
            'accessories' => 'nullable|string',
            'status'      => ['required', Rule::in(array_column(ItemStatus::cases(), 'value'))],
        ]);
        
        // Update data item dengan data yang sudah tervalidasi
        $item->update($validatedData);
        
        return response()->json([
            'message' => 'Barang berhasil diupdate!',
            'item' => $item // Kembalikan data item yang sudah terupdate
        ]);
    }

    /**
     * Menghapus data barang dari database.
     * Laravel secara otomatis menemukan $item berdasarkan {id} di URL (Route Model Binding)
     */
    public function destroy(Item $item)
    {
        // Hapus item dari database
        $item->delete();
        
        // Kembalikan response sukses
        return response()->json(['message' => 'Barang berhasil dihapus.']);
    }

    /**
     * Menampilkan detail satu barang spesifik.
     */
    public function show(Item $item)
    {
        return response()->json($item);
    }
}

