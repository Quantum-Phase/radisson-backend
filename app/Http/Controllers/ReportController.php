<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use App\Models\SubLedger;
use App\Models\Payment;
use App\Models\PaymentMode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function ledgerReport(Request $request)
    {

        $date = Carbon::parse($request->date) ?? Carbon::now();
        $ledgerId = $request->ledgerId;
        $subLedgerId = $request->subLedgerId;

        // Get the ledger details
        $ledger = Ledger::findOrFail($ledgerId);

        // Calculate opening balance (sum of all payments before the given date)
        $openingBalanceQuery = Payment::where('ledgerId', $ledgerId)
            ->whereDate('created_at', '<', $date);

        if ($subLedgerId) {
            $openingBalanceQuery->where('subLedgerId', $subLedgerId);
        }

        $openingBalance = $openingBalanceQuery->sum('amount');

        // Get payments for the specified date
        $paymentsQuery = Payment::with(['paymentMode', 'subLedger', 'student'])
            ->where('ledgerId', $ledgerId)
            ->whereDate('created_at', $date);

        if ($subLedgerId) {
            $paymentsQuery->where('subLedgerId', $subLedgerId);
        }

        $payments = $paymentsQuery->get();

        // Calculate closing balance considering payment types
        $closingBalance = $openingBalance;
        foreach ($payments as $payment) {
            if (in_array($payment->type, ['income', 'assets'])) {
                $closingBalance += $payment->amount;
            } else if (in_array($payment->type, ['expense', 'liability'])) {
                $closingBalance -= $payment->amount;
            }
        }

        // Format the response
        $response = [
            'ledger' => [
                'id' => $ledger->ledgerId,
                'name' => $ledger->name
            ],
            'date' => $date->format('Y-m-d'),
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'payments' => $payments->sortBy('created_at')->map(function ($payment, $index) use ($openingBalance, $payments) {
                // Calculate running balance by adding opening balance to all previous payments
                $runningBalance = $openingBalance;
                for ($i = 0; $i <= $index; $i++) {
                    $currentPayment = $payments[$i];
                    // Add amount for income/assets, subtract for expense/liability
                    if (in_array($currentPayment->type, ['income', 'assets'])) {
                        $runningBalance += $currentPayment->amount;
                    } else if (in_array($currentPayment->type, ['expense', 'liability'])) {
                        $runningBalance -= $currentPayment->amount;
                    }
                }

                return [
                    'id' => $payment->paymentId,
                    'amount' => $payment->amount,
                    'type' => $payment->type,
                    'paymentMode' => $payment->paymentMode ? $payment->paymentMode->name : null,
                    'subLedger' => $payment->subLedger ? $payment->subLedger->name : null,
                    'remarks' => $payment->remarks,
                    'created_at' => $payment->created_at,
                    'balance' => $runningBalance,
                    'student' => $payment->student ? [
                        'id' => $payment->student->userId,
                        'name' => $payment->student->name
                    ] : null
                ];
            })
        ];

        if ($subLedgerId) {
            $subLedger = SubLedger::findOrFail($subLedgerId);
            $response['subLedger'] = [
                'id' => $subLedger->subLedgerId,
                'name' => $subLedger->name
            ];
            $response['reportType'] = 'subledger';
        } else {
            $response['reportType'] = 'ledger';
        }

        return response()->json($response);
    }

    public function paymentModeReport(Request $request)
    {
        $date = Carbon::parse($request->date) ?? Carbon::now();
        $paymentModeId = $request->paymentModeId;

        // Get the payment mode details
        $paymentMode = PaymentMode::findOrFail($paymentModeId);

        // Calculate opening balance (sum of all payments before the given date)
        $openingBalanceQuery = Payment::where('paymentModeId', $paymentModeId)
            ->whereDate('created_at', '<', $date);

        $openingBalance = $openingBalanceQuery->sum('amount');

        // Get payments for the specified date
        $paymentsQuery = Payment::with(['ledger', 'subLedger', 'student'])
            ->where('paymentModeId', $paymentModeId)
            ->whereDate('created_at', $date);

        $payments = $paymentsQuery->get();

        // Calculate closing balance considering payment types
        $closingBalance = $openingBalance;
        foreach ($payments as $payment) {
            if (in_array($payment->type, ['income', 'assets'])) {
                $closingBalance += $payment->amount;
            } else if (in_array($payment->type, ['expense', 'liability'])) {
                $closingBalance -= $payment->amount;
            }
        }

        // Format the response
        $response = [
            'paymentMode' => [
                'id' => $paymentMode->paymentModeId,
                'name' => $paymentMode->name
            ],
            'date' => $date->format('Y-m-d'),
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'payments' => $payments->sortBy('created_at')->map(function ($payment, $index) use ($openingBalance, $payments) {
                // Calculate running balance by adding opening balance to all previous payments
                $runningBalance = $openingBalance;
                for ($i = 0; $i <= $index; $i++) {
                    $currentPayment = $payments[$i];
                    // Add amount for income/assets, subtract for expense/liability
                    if (in_array($currentPayment->type, ['income', 'assets'])) {
                        $runningBalance += $currentPayment->amount;
                    } else if (in_array($currentPayment->type, ['expense', 'liability'])) {
                        $runningBalance -= $currentPayment->amount;
                    }
                }

                return [
                    'id' => $payment->paymentId,
                    'amount' => $payment->amount,
                    'type' => $payment->type,
                    'ledger' => $payment->ledger ? [
                        'id' => $payment->ledger->ledgerId,
                        'name' => $payment->ledger->name
                    ] : null,
                    'subLedger' => $payment->subLedger ? [
                        'id' => $payment->subLedger->subLedgerId,
                        'name' => $payment->subLedger->name
                    ] : null,
                    'remarks' => $payment->remarks,
                    'created_at' => $payment->created_at,
                    'balance' => $runningBalance,
                    'student' => $payment->student ? [
                        'id' => $payment->student->userId,
                        'name' => $payment->student->name
                    ] : null
                ];
            })
        ];

        return response()->json($response);
    }
}
