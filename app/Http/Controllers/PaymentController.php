<?php

namespace App\Http\Controllers;

use App\Models\Batch;
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
        $payed_by = $request->payerId;

        $payments = Payment::with([
            'payedBy:userId,name,phoneNo',
            'receivedBy:userId,name',
            'batch' => function ($query) {
                $query->select('batchId', 'name', 'courseId');
            },
            'batch.course' => function ($query) {
                $query->select('courseId', 'name');
            },
            'paymentMode' => function ($query) {
                $query->select('paymentModeId', 'name');
            },
            'block'
        ])
            ->when($payed_by, function ($query) use ($payed_by) {
                $query->where('payed_by', $payed_by);
            })
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('payedBy', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('batch', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('receivedBy', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
            })
            ->orderBy('created_at', 'desc');

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
            'paymentModeId' => 'required',
            'name' => 'required|string',
            'type' => 'required|string',
            'blockId' => 'required|exists:blocks,blockId'
        ]);
        $user = auth()->user();

        $userFeeDetail = UserFeeDetail::where("userId", $request->payed_by)->first();

        if ($request->type === "receive"  && $request->payed_by) {

            if (!$userFeeDetail) {
                return response()->json(['message' => 'User Fee detail not found'], 404);
            }

            if ($userFeeDetail->remainingAmount < $request->amount) {
                return response()->json(['message' => 'Paying amount cannot be greater than remaining amount.'], 404);
            }
        }

        $payment = new Payment();
        $payment->amount = $request->amount;
        $payment->payed_by = $request->payed_by;
        $payment->batchId = $request->batchId;
        $payment->paymentModeId = $request->paymentModeId;
        $payment->blockId = $request->blockId;
        $payment->remarks = $request->remarks;
        $payment->name = $request->name;
        $payment->type = $request->type;
        $payment->received_by = $user->userId;

        if ($request->type === 'receive' && $request->payed_by) {
            $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;

            $payment->due_amount = $userFeeDetail->amountToBePaid -  $userFeeDetail->totalAmountPaid;
        }

        $payment->save();
        if ($request->type === 'receive' && $request->payed_by) {
            $userFeeDetail->update();
        }
        return response()->json('Payment inserted successfully');
    }
}
