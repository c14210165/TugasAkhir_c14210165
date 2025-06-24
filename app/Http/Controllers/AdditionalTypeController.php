<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdditionalItemType;

class AdditionalTypeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:additional_item_types,name'
        ]);
        $type = AdditionalItemType::create(['name' => $request->name]);
        return response()->json($type, 201);
    }
}
