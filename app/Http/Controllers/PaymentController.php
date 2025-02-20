<?php

namespace App\Http\Controllers;

use App\Constants\LedgerType;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\UserFeeDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = (int)$request->limit;
        $search = $request->search;
        $studentId = $request->payerId;

        $user = auth()->user();

        $types = is_string($request->query('type')) ? explode(',', $request->query('type')) : [$request->query('type')];

        $payments = Payment::with([
            'student:userId,name,phoneNo',
            'transactionBy:userId,name',
            'batch' => function ($query) {
                $query->select('batchId', 'name', 'courseId');
            },
            'ledger' => function ($query) {
                $query->select('ledgerId', 'name');
            },
            'subLedger' => function ($query) {
                $query->select('subLedgerId', 'name');
            },
            'batch.course' => function ($query) {
                $query->select('courseId', 'name');
            },
            'paymentMode' => function ($query) {
                $query->select('paymentModeId', 'name');
            },
            'block'
        ])
            ->when($studentId, function ($query) use ($studentId) {
                $query->where('studentId', $studentId);
            })
            ->when($types, function ($query) use ($types) {
                return $query->whereIn('type', $types);
            })
            ->where(function ($query) use ($search) {
                $query->whereHas('student', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                    $query->orWhere('phoneNo', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('batch', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('transactionBy', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
            })
            ->orderBy('created_at', 'desc');

        if ($user->role !== "superadmin") {
            $payments->where("transaction_by", $user->userId);
        }

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
            'amount' => 'required|numeric',
            'type' => 'required|string',
            'blockId' => 'required|exists:blocks,blockId',
            'ledgerId' => 'required|exists:ledgers,ledgerId',
            'subLedgerId' => 'required|exists:sub_ledgers,subLedgerId',
            'paymentModeId' => 'required|exists:payment_modes,paymentModeId',
            'remarks' => 'nullable|string',
        ]);
        $user = auth()->user();

        if ($request->amount <= 0) {
            return response()->json(['error' => 'Invalid amount'], 422);
        }

        $payment = new Payment();
        $payment->amount = $request->amount;
        $payment->studentId = $request->studentId;
        $payment->batchId = $request->batchId;
        $payment->ledgerId = $request->ledgerId;
        $payment->subLedgerId = $request->subLedgerId;
        $payment->paymentModeId = $request->paymentModeId;
        $payment->blockId = $request->blockId;
        $payment->remarks = $request->remarks;
        $payment->type = $request->type;
        $payment->transaction_by = $user->userId;

        $ledger = Ledger::find($request->ledgerId);

        if ($ledger->isStudentFeeLedger) {
            $userFeeDetail = UserFeeDetail::where("userId", $request->studentId)->where("batchId", $request->batchId)->first();

            if (!$userFeeDetail) {
                return response()->json(['message' => 'User Fee detail not found'], 404);
            }

            if ($userFeeDetail->remainingAmount < $request->amount) {
                return response()->json(['message' => 'Paying amount cannot be greater than remaining amount.'], 404);
            }

            $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;
            $userFeeDetail->update();
        }

        if ($ledger->isStudentRefundLedger) {
            $userFeeDetail = UserFeeDetail::where("userId", $request->studentId)->where("batchId", $request->batchId)->first();

            if (!$userFeeDetail) {
                return response()->json(['message' => 'User Fee detail not found'], 404);
            }

            if ($userFeeDetail->refundRequestedAmount < $request->amount) {
                return response()->json(['message' => 'Paying amount cannot be greater than refund amount.'], 404);
            }

            $userFeeDetail->refundedAmount = $userFeeDetail->refundedAmount + $request->amount;
            $userFeeDetail->update();
        }

        $payment->save();

        // if ($request->type && $request->actionType && $request->batchId) {
        //     $userFeeDetail = UserFeeDetail::where("userId", $request->studentId)->where("batchId", $request->batchId)->first();

        //     if (!$userFeeDetail) {
        //         return response()->json(['message' => 'User Fee detail not found'], 404);
        //     }

        //     if ($request->type === LedgerType::INCOME && $request->actionType === "fee") {
        //         if ($userFeeDetail->remainingAmount < $request->amount) {
        //             return response()->json(['message' => 'Paying amount cannot be greater than remaining amount.'], 404);
        //         }

        //         $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;

        //         $payment->due_amount = $userFeeDetail->amountToBePaid -  $userFeeDetail->totalAmountPaid;
        //     }

        //     if ($request->type === LedgerType::EXPENSE && $request->actionType === "refund") {
        //         if ($userFeeDetail->refundAmount < $request->amount) {
        //             return response()->json(['message' => 'Paying amount cannot be greater than refund amount.'], 404);
        //         }

        //         $userFeeDetail->refundAmount = $userFeeDetail->refundAmount - $request->amount;
        //     }

        //     $payment->save();
        //     $userFeeDetail->update();
        // }

        // if ($request->type === LedgerType::INCOME && $request->batchId) {
        //     $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid + $request->amount;

        //     $payment->due_amount = $userFeeDetail->amountToBePaid -  $userFeeDetail->totalAmountPaid;
        // }
        // if ($request->type !== LedgerType::INCOME || !$request->batchId) {
        //     $payment->save();
        // }

        // $ledger = Ledger::find($request->ledgerId);
        // $ledger->amount = $ledger->amount + $request->amount;
        // $ledger->update();
        // if ($request->type === LedgerType::INCOME && $request->payed_by) {
        //     $userFeeDetail->update();
        // }
        return response()->json('Payment inserted successfully');
    }

    public function getFinancialOverview(Request $request)
    {
        $financeType = $request->financeType;
        $dateType = $request->dateType ? $request->dateType : "today";
        $user = auth()->user();

        $type = [];

        if ($financeType === "balance-sheet") {
            $type = [LedgerType::LIABILITY, LedgerType::ASSETS];
        } elseif ($financeType === "profit-loss") {
            $type = [LedgerType::INCOME, LedgerType::EXPENSE];
        } else {
            return response()->json(['message' => "Invalid finance type"], 422);
        }

        // Start the query for payments
        $paymentsQuery = Payment::with([
            'ledger' => function ($query) {
                $query->select('ledgerId', 'name');
            },
            'subLedger' => function ($query) {
                $query->select('subLedgerId', 'name');
            },
            'paymentMode' => function ($query) {
                $query->select('paymentModeId', 'name');
            },
        ])
            ->when($type, function ($query, $type) {
                return $query->whereIn("payments.type", $type);
            });

        // Apply date filtering based on dateType
        $today = Carbon::today();
        switch ($dateType) {
            case 'today':
                $paymentsQuery->whereDate('created_at', $today);
                break;
            case 'weekly':
                $startOfWeek = $today->copy()->startOfWeek(); // Default is Sunday
                $endOfWeek = $today->copy()->endOfWeek(); // Default is Saturday
                $paymentsQuery->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                break;
            case 'monthly':
                $paymentsQuery->whereMonth('created_at', $today->month)
                    ->whereYear('created_at', $today->year);
                break;
            case 'yearly':
                $paymentsQuery->whereYear('created_at', $today->year);
                break;
            default:
                // Optionally handle 'daily' or other cases if needed
                break;
        }

        if ($user->role !== "superadmin") {
            $paymentsQuery->where("transaction_by", $user->userId);
        }

        // Execute the query and group the results by payment type
        $payments = $paymentsQuery->get()->groupBy('type');

        // Initialize an array to hold the formatted response
        $formattedResponse = [];

        // Define the expected types
        $expectedTypes = $type;

        // Loop through each expected type
        foreach ($expectedTypes as $expectedType) {
            // Check if there are payments for this type
            if (isset($payments[$expectedType])) {
                $totalAmountByType = $payments[$expectedType]->sum('amount');
                $paymentsByType = $payments[$expectedType];

                // Group by ledger name
                $groupedByLedger = $paymentsByType->groupBy('ledger.name');

                // Initialize an array for this type
                $ledgerArray = [];

                // Loop through each ledger and format the response
                foreach ($groupedByLedger as $ledgerName => $paymentsByLedger) {
                    // Calculate total amount for this ledger
                    $totalAmountByLedger = $paymentsByLedger->sum('amount');

                    // Prepare the ledger response
                    $ledgerResponse = [
                        'ledgerName' => $ledgerName,
                        'total' => $totalAmountByLedger, // Total for this ledger
                        'payments' => $paymentsByLedger->map(function ($payment) {
                            return [
                                'id' => $payment->paymentId,
                                'name' => $payment->subLedger ? $payment->subLedger->name : 'Unknown Sub Ledger',
                                'paymentMode' => $payment->paymentMode ? $payment->paymentMode->name : 'Unknown Payment Mode',
                                'amount' => $payment->amount,
                            ];
                        })->toArray()
                    ];

                    // Add the ledger response to the ledger array
                    $ledgerArray[] = $ledgerResponse;
                }

                // Add the type and its ledgers to the formatted response
                $formattedResponse[] = [
                    'type' => $expectedType,
                    'totalAmountByType' => $totalAmountByType,
                    'ledger' => $ledgerArray,
                ];
            } else {
                // If no payments for this type, add an empty ledger array
                $formattedResponse[] = [
                    'type' => $expectedType,
                    'ledger' => [],
                    'totalAmountByType' => 0
                ];
            }
        }

        // Return the formatted response as JSON
        return response()->json($formattedResponse);
    }

    public function editPayment(Request $request, $paymentId)
    {
        $request->validate([
            'amount' => 'required',
            'paymentModeId' => 'required',
            'blockId' => 'required|exists:blocks,blockId',
        ]);

        $payment = Payment::find($paymentId);

        if (!$payment) {
            return response()->json(['message' => "Payment doesn't exists."], 404);
        }

        // Get the created_at date of the payment
        $createdAt = Carbon::parse($payment->created_at);

        // Get today's date
        $today = Carbon::now();

        // Check if created_at exceeds today's date
        if ($createdAt->gt($today)) {
            return response()->json(['message' => 'Payment created_at date exceeds today\'s date'], 422);
        }

        $ledger = Ledger::find($payment->ledgerId);

        if ($ledger->isStudentFeeLedger) {
            $userFeeDetail = UserFeeDetail::where("userId", $payment->studentId)->where("batchId", $payment->batchId)->first();

            if (!$userFeeDetail) {
                return response()->json(['message' => 'User Fee detail not found'], 404);
            }

            if (($userFeeDetail->remainingAmount + $payment->amount) < $request->amount) {
                return response()->json(['message' => 'Paying amount cannot be greater than remaining amount.'], 422);
            }
            $userFeeDetail->totalAmountPaid = $userFeeDetail->totalAmountPaid - $payment->amount + $request->amount;
            $userFeeDetail->update();
        }

        if ($ledger->isStudentRefundLedger) {
            $userFeeDetail = UserFeeDetail::where("userId", $payment->studentId)->where("batchId", $payment->batchId)->first();

            if (!$userFeeDetail) {
                return response()->json(['message' => 'User Fee detail not found'], 404);
            }

            if (($userFeeDetail->refundedAmount + $request->amount) < $request->refundRequestedAmount) {
                return response()->json(['message' => 'Paying amount cannot be greater than refund amount.'], 422);
            }
            $userFeeDetail->refundedAmount = $userFeeDetail->refundedAmount + $request->amount;
            $userFeeDetail->update();
        }

        $payment->amount = $request->amount;
        $payment->paymentModeId = $request->paymentModeId;
        $payment->blockId = $request->blockId;
        $payment->remarks = $request->remarks;

        $payment->update();
        return response()->json('Payment updated successfully');
    }
}
