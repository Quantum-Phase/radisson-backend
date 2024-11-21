<?php

namespace App\Http\Controllers;

use App\Constants\LedgerType;
use App\Models\Block;
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
        $payed_by = $request->payerId;

        $types = is_string($request->query('type')) ? explode(',', $request->query('type')) : [$request->query('type')];

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
            ->when($types, function ($query) use ($types) {
                return $query->whereIn('type', $types);
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

    public function getFinancialOverview(Request $request)
    {
        $financeType = $request->financeType;
        $dateType = $request->dateType ? $request->dateType : "today";

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
                                'name' => $payment->name,
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
}
