<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;





class DailyTransactionController extends Controller
{
    public function showTransaction(Request $request)
    {
        // Get today's date
        $today = now()->startOfDay();

        // Initialize totals
        $openingBalance = 0;
        $totalCredit = 0;
        $totalDebit = 0;
        $result = [];

        // Fetch payments made today
        $payments = Payment::whereDate('created_at', '=', $today)
        ->orderBy('blockId', 'asc')
        ->orderBy('created_at', 'desc')
        ->get(['paymentId', 'name', 'type', 'amount', 'blockId']); // Select only the required fields

        // Process payments and group by block name and type
        foreach ($payments as $payment) {
            // Ensure block name is fetched
            $blockName = Block::find($payment->blockId)->name ?? 'Unknown Block'; // Handle if block is not found

            if (!isset($result[$payment->blockId])) {
                $result[$payment->blockId] = [
                    'blockName' => $blockName,
                    'credit' => [],
                    'debit' => [],
                    'totalCredit' => 0,
                    'totalDebit' => 0,
                ];
            }

            if ($payment->type == 'credit') {
                $totalCredit += $payment->amount;
                $result[$payment->blockId]['credit'][] = [
                    'paymentId' => $payment->paymentId,
                    'name' => $payment->name,
                    'type' => $payment->type,
                    'amount' => $payment->amount,
                ];
                $result[$payment->blockId]['totalCredit'] += $payment->amount;
            } elseif ($payment->type == 'debit') {
                $totalDebit += $payment->amount;
                $result[$payment->blockId]['debit'][] = [
                    'paymentId' => $payment->paymentId,
                    'name' => $payment->name,
                    'type' => $payment->type,
                    'amount' => $payment->amount,
                ];
                $result[$payment->blockId]['totalDebit'] += $payment->amount;
            }
        }

        // Format the final result
        $finalResult = [];
        foreach ($result as $blockId => $data) {
            $finalResult[] = [
                'blockId' => $blockId,
                'blockName' => $data['blockName'],
                'credit' => [
                    'total' => $data['totalCredit'],
                    'payments' => $data['credit'],
                ],
                'debit' => [
                    'total' => $data['totalDebit'],
                    'payments' => $data['debit'],
                ],
            ];
        }

        // Return results or pass them to a view
        return response()->json([
            'openingBalance' => $openingBalance,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'data' => $finalResult,
        ]);
    }
}
