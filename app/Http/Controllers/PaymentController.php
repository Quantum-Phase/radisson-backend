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
        ->leftJoin('users', 'payments.payed_by', '=', 'users.userId')
        ->leftJoin('courses', 'payments.courseId', '=', 'courses.courseId')
        ->leftJoin('users as received_by_users', 'payments.received_by', '=', 'received_by_users.userId')
        ->addSelect([
            'paid_by' => User::select('name')->whereColumn('userId', 'payments.payed_by')->limit(1),
            'payer_mobile_number' => User::select('phoneNo')->whereColumn('userId', 'payments.payed_by')->limit(1),
            'course_name' => Course::select('name')->whereColumn('courseId', 'payments.courseId')->limit(1),
            'payment_received_by' => User::select('name')->whereColumn('userId', 'payments.received_by')->limit(1),
            'block_name' => Block::select('name')->whereColumn('blockId', 'payments.blockId')->limit(1),
            'payed_by' // Including payed_by explicitly in the select
        ])
        ->where(function ($query) use ($search) {
            $query->where('payments.name', 'like', '%' . $search . '%')
                ->orWhere('users.name', 'like', '%' . $search . '%')
                ->orWhere('courses.name', 'like', '%' . $search . '%')
                ->orWhere('received_by_users.name', 'like', '%' . $search . '%');
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
            'payment_mode' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'blockId' => 'required|exists:blocks,blockId'
        ]);
        $user = auth()->user();
        $userFeeDetail = UserFeeDetail::where("userId", $request->payed_by)->first();

        if (!$userFeeDetail) {
            return response()->json(['message' => 'User Fee detail not found'], 404);
        }

        if ($userFeeDetail->remainingAmount < $request->amount) {
            return response()->json(['message' => 'Paying amount cannot be greater than remaining amount.'], 404);
        }

        $payment = new Payment();
        $payment->amount = $request->amount;
        $payment->payed_by = $request->payed_by;
        $payment->courseId = $request->courseId;
        $payment->payment_mode = $request->payment_mode;
        $payment->blockId = $request->blockId;
        $payment->remarks = $request->remarks;
        $payment->name = $request->name;
        $payment->type = $request->type;
        $payment->received_by = $user->userId;

        if ($request->type === 'credit' && $request->payed_by) {           
            $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;
            $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;

            $payment->due_amount = $userFeeDetail->amountToBePaid -  $userFeeDetail->totalAmountPaid;
        }

        $payment->save();
        if ($request->type === 'credit' && $request->payed_by) {           
            $userFeeDetail->update();
        }
        return response()->json('Payment inserted successfully');
    }
}
