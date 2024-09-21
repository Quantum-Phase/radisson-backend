<?php

namespace App\Http\Controllers;

use App\Models\Block;
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
            // Adding user name and course name as part of the select
            ->addSelect([
                'paid_by' => User::select('name')->whereColumn('userId', 'payments.payed_by')->limit(1),
                'course_name' => Course::select('name')->whereColumn('courseId', 'payments.courseId')->limit(1),
                'payment_received_by' => User::select('name')->whereColumn('userId', 'payments.received_by')->limit(1),
                'block_name' => Block::select('name')->whereColumn('blockId', 'payments.blockId')->limit(1),
                'mobile_number' => User::select('phoneNo')->whereColumn('userId', 'payments.payed_by')->limit(1),
                'payed_by' // Including payed_by explicitly in the select
            ])
            ->where(function ($query) use ($search) {
                $query->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('courses.name', 'like', '%' . $search . '%');
            });

        // Paginate if limit is provided, else get all
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
            // 'recieved_by' => 'required|exists:users,userId',
            'blockId' => 'required|exists:blocks,blockId'
        ]);
        // dd($request->received_by);
        $payment = new Payment();
        $payment->amount = $request->amount;
        $payment->payed_by = $request->payed_by;
        $payment->courseId = $request->courseId;
        $payment->payment_mode = $request->payment_mode;
        $payment->received_by = $request->received_by;
        $payment->blockId = $request->blockId;
        $payment->remarks = $request->remarks;
        $payment->name = $request->name;
        $payment->type = $request->type;

        $payment->save();
        if ($request->type == 'credit' && $request->payed_by) {
            $userFeeDetail = UserFeeDetail::where("userId", $request->payed_by)->first();

            if (!$userFeeDetail) {
                return response()->json(['message' => 'User Fee detail not found'], 404);
            }

            if ($userFeeDetail->remainingAmount < $request->amount) {
                return response()->json(['message' => 'Paying amount cannot be greater than remaining amount.'], 404);
            }

            $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;
            $userFeeDetail->update();
        }
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
