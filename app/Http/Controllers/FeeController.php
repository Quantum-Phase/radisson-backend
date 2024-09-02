<?php

namespace App\Http\Controllers;

use App\Models\Payment;


use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function showFee()
    {
        $fee_data = Payment::all();
        return response()->json($fee_data);
    }
}
