<?php

namespace App\Http\Controllers;

use App\Models\AccountantBlock;
use App\Models\CourseFee;
use App\Models\Payment;
use App\Models\UserPayment;
use Illuminate\Support\Facades\Auth;



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
        $insertFee->amount = $request->amount;
        $insertFee->payment_mode = $request->payment_mode;
        $insertFee->payment_made = $request->payment_made;
        $insertFee->save();

        $receiptNo = 'RCT-' . $insertFee->feeId;
        $insertFee->receipt_no = $receiptNo;
        $insertFee->save();

        $feeblock = new UserPayment;
        $feeblock->userId = $request->userId;
        $feeblock->feeId = $insertFee->feeId;
        $feeblock->feeId = $request->batchId;
        $feeblock->save();

        $coursefee = new CourseFee;
        $coursefee->courseId = $request->courseId;
        $coursefee->feeId = $insertFee->feeId;
        $coursefee->save();
        return response()->json('Payment Inserted Sucessfully');
    }

    // public function deleteFee($feeId)
    // {
    //     $deleteFee = Payment::find($feeId);
    //     $deleteFee->delete();
    //     return response()->json('Payment Deleted Sucessfully');
    // }

    // public function updateFee(Request $request, $feeId)
    // {
    //     $updateFee = Payment::find($feeId);
    //     $updateFee->source = $request->source;
    //     $updateFee->amount = $request->amount;
    //     $updateFee->installment = $request->installment;
    //     $updateFee->paid = $request->paid;
    //     $updateFee->update();
    //     return response()->json('User payment Updated Sucessfully');
    // }
}
