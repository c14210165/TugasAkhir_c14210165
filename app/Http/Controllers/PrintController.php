<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function handover(Loan $loan)
    {
        // Pastikan relasi yang dibutuhkan sudah dimuat
        $loan->load(['item', 'requester', 'checkedOutBy']);

        return view('print.handover', compact('loan'));
    }
}
