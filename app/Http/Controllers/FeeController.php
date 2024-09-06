<?php

namespace App\Http\Controllers;

use App\Models\Payment;


use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function searchFee(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
            return response()->json([
                'message' => "Query parameter is required"
            ], 400);
        } else {
            $searchfee = Payment::where('name', 'LIKE', '%' . $search . '%')
                ->get();
            return response()->json($searchfee);
        }
    }
    public function showFee()
    {
        $fee_data = Payment::all();
        return response()->json($fee_data);
    }

    public function insertFee(Request $request)
    {
        $insertFee = new Payment;
        $insertFee->source = $request->source;
        $insertFee->amount = $request->amount;
        $insertFee->save();
        return response()->json('Payment Inserted Sucessfully');
    }

    public function deleteFee($feeId)
    {
        $deleteFee = Payment::find($feeId);
        $deleteFee->delete();
        return response()->json('Payment Deleted Sucessfully');
    }

    public function updateFee(Request $request, $feeId)
    {
        $updateFee = Payment::find($feeId);
        $updateFee->source = $request->source;
        $updateFee->amount = $request->amount;
        $updateFee->update();
        return response()->json('User payment Updated Sucessfully');
    }
}
