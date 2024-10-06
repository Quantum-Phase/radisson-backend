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

        // Initialize totals
        $openingBalance = 0;
        $totalCredit = 0;
        $totalDebit = 0;
        $result = [];

        $user = auth()->user();
        $blockId = $request->blockId;

        // Get today's date
        $today = now()->startOfDay();
        $openingblnc = DB::table('daily_transactions')->whereDate('created_at', $today)->first();
        if ($openingblnc) {
            $openingBalance = $openingblnc->opening_balance;
        }

        // Fetch payments made today
        $paymentsQuery = Payment::whereDate('created_at', '=', $today);

        if ($user->role === 'accountant') {
            $paymentsQuery->where('blockId', $user->blockId);
        } else if ($blockId && $blockId !== 'all') {
            $paymentsQuery->where('blockId', $blockId);
        } else {
            $paymentsQuery->orderBy('blockId', 'asc');
        }

        $payments = $paymentsQuery
            ->orderBy('created_at', 'desc')
            ->get(['paymentId', 'name', 'type', 'amount', 'blockId']);

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
        // dd($openingblnc);

        // Return results or pass them to a view
        return response()->json([
            'openingBalance' => $openingBalance,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'data' => $finalResult,
            'date' => $today
        ]);
    }
}
