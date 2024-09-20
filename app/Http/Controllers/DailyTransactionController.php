<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;


class DailyTransactionController extends Controller
{
    public function showTransaction(Request $request)
    {
        $payments = Payment::all();
        $openingbalance = 0;
        $totalCredit = 0;
        $totalDebit = 0;
        $creditGroup = [];
        $debitGroup = [];

        foreach ($payments as $payment) {
            if (isset($payment->type) && $payment->type == 'credit') {
                $creditGroup[] = [
                    'name' => $payment->name,
                    'amount' => $payment->amount,
                    'type' => $payment->type,
                    'payment_mode' => $payment->payment_mode,
                    'remarks' => $payment->remarks,
                    'blockId' => $payment->blockId,
                    // 'totalCredit' => $totalCredit + $payment->amount,
                ];
                $totalCredit += $payment->amount;
            } else {
                $debitGroup[] = [
                    'name' => $payment->name,
                    'amount' => $payment->amount,
                    'type' => $payment->type,
                    'payment_mode' => $payment->payment_mode,
                    'remarks' => $payment->remarks,
                    'blockId' => $payment->blockId
                ];
                $totalDebit += $payment->amount;
            }
        }
        $openingbalance = $totalCredit - $totalDebit;
        return response()->json([
            'creditGroup' => $creditGroup,
            'debitGroup' => $debitGroup,
            'openingbalance' => $openingbalance,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
        ]);
    }

    public function insertTransaction(Request $request) {}
}
