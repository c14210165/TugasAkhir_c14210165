<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemType;

class ItemTypeController extends Controller
{
    public function store(Request $request)
    {
        // Validasi: nama harus diisi, unik (tidak boleh ada yang sama di tabel item_types), dan maksimal 255 karakter.
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:item_types,name'
        ]);

        $itemType = ItemType::create($validatedData);

        return response()->json([
            'message' => 'Tipe barang baru berhasil ditambahkan!',
            'item_type' => $itemType
        ], 201); // 201 Created
    }
}
