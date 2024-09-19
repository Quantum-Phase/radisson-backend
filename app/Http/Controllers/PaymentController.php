<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserFeeDetail;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;
    
        $payments = Payment::select('payments.*')
                          ->join('users', 'payments.payed_by', '=', 'users.userId')
                          ->join('courses', 'payments.courseId', '=', 'courses.courseId')
                          ->addSelect(['user' => User::select('name')->whereColumn('userId', 'payments.payed_by')])
                          ->addSelect(['course' => Course::select('name')->whereColumn('courseId', 'payments.courseId')])
                          ->where(function ($query) use ($search) {
                              $query->where('users.name', 'like', '%' . $search . '%')
                                    ->orWhere('courses.name', 'like', '%' . $search . '%');
                          });
    
        if ($request->has('limit')) {
            $payments = $payments->paginate($limit);
        } else {
            $payments = $payments->get();
        }
    
        return response()->json($payments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $request->validate([
            'amount' => 'required',
            'payed_by' => 'required|exists:users,userId',
            'courseId' => 'required|exists:courses,courseId',
            'payment_mode' => 'required|string',
            'recieved_by' => 'required|exists:users,userId',
            'blockId' => 'required|exists:blocks,blockId'
        ]);

        $payment = new Payment();
        $payment->amount = $request->amount;
        $payment->payed_by = $request->payed_by;
        $payment->courseId = $request->courseId;
        $payment->payment_mode = $request->payment_mode;
        $payment->recieved_by = $request->recieved_by;
        $payment->blockId = $request->blockId;
        $payment->remarks = $request->remarks;

        $payment->save();

        $userFeeDetail = UserFeeDetail::where("userId", $request->payed_by)->first();

        if (!$userFeeDetail) {
            return response()->json(['message' => 'User Fee detail not found'], 404);
        }

        if ($userFeeDetail->remainingAmount < $request->amount) {
            return response()->json(['message' => 'Paying amount cannot be greater than remaining amount.'], 404);
        }

        $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;
        $userFeeDetail->update();

        return response()->json('Payment inserted successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
