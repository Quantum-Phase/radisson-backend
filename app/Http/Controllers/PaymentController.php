<?php

namespace App\Http\Controllers;

use App\Constants\LedgerType;
use App\Models\Ledger;
use App\Models\Payment;
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
            'transactionBy:userId,name',
            'batch' => function ($query) {
                $query->select('batchId', 'name', 'courseId');
            },
            'ledger' => function ($query) {
                $query->select('ledgerId', 'name');
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
                    ->orWhereHas('transactionBy', function ($query) use ($search) {
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
            'blockId' => 'required|exists:blocks,blockId',
        ]);
        $user = auth()->user();


        if ($request->amount <= 0) {
            return response()->json(['error' => 'Invalid amount'], 422);
        }

        $payment = new Payment();
        $payment->amount = $request->amount;
        $payment->payed_by = $request->payed_by;
        $payment->batchId = $request->batchId;
        $payment->ledgerId = $request->ledgerId;
        $payment->paymentModeId = $request->paymentModeId;
        $payment->blockId = $request->blockId;
        $payment->remarks = $request->remarks;
        $payment->name = $request->name;
        $payment->type = $request->type;
        $payment->transaction_by = $user->userId;

        if ($request->type === LedgerType::INCOME  && $request->batchId) {
            $userFeeDetail = UserFeeDetail::where("userId", $request->payed_by)->first();

            if (!$userFeeDetail) {
                return response()->json(['message' => 'User Fee detail not found'], 404);
            }

            if ($userFeeDetail->remainingAmount < $request->amount) {
                return response()->json(['message' => 'Paying amount cannot be greater than remaining amount.'], 404);
            }

            $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;

            $payment->due_amount = $userFeeDetail->amountToBePaid -  $userFeeDetail->totalAmountPaid;

            $payment->save();
            $userFeeDetail->update();
        }

        // if ($request->type === LedgerType::INCOME && $request->batchId) {
        //     $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;

        //     $payment->due_amount = $userFeeDetail->amountToBePaid -  $userFeeDetail->totalAmountPaid;
        // }
        if ($request->type !== LedgerType::INCOME || !$request->batchId) {
            $payment->save();
        }

        $ledger = Ledger::find($request->ledgerId);
        $ledger->amount = $ledger->amount + $request->amount;
        $ledger->update();
        // if ($request->type === LedgerType::INCOME && $request->payed_by) {
        //     $userFeeDetail->update();
        // }
        return response()->json('Payment inserted successfully');
    }
}
