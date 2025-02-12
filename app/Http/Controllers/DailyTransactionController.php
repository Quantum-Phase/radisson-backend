<?php

namespace App\Http\Controllers;

use App\Constants\LedgerType;
use App\Models\Block;
use App\Models\Ledger;
use App\Models\Payment;
use Carbon\Carbon;
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
        $transactionBy = $request->transactionBy;

        $date = $request->date ? Carbon::parse($request->date)->startOfDay() : now()->startOfDay();

        $openingblnc = DB::table('daily_transactions')->whereDate('created_at', $date)->first();
        if ($openingblnc) {
            $openingBalance = $openingblnc->opening_balance;
        }

        // Fetch payments made today
        $paymentsQuery = Payment::whereDate('created_at', '=', $date);

        if ($user->role !== 'superadmin') {
            $paymentsQuery->where('blockId', $user->blockId)->where('transaction_by', $user->userId);
        } else if ($blockId && $blockId !== 'all') {
            $paymentsQuery->where('blockId', $blockId);
        } else {
            $paymentsQuery->orderBy('blockId', 'asc');
        }

        $payments = $paymentsQuery
            ->orderBy('created_at', 'desc')
            ->when($transactionBy, function ($query) use ($transactionBy) {
                $query->where('transaction_by', $transactionBy);
            })
            ->get(['paymentId', 'name', 'type', 'amount', 'blockId', 'ledgerId']);

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

            if ($payment->type === LedgerType::INCOME) {
                $result[$payment->blockId]['totalCredit'] += $payment->amount;
                $totalCredit += $payment->amount;  // Add to overall total
                if (!isset($result[$payment->blockId]['credit'][$payment->ledgerId])) {
                    $ledgerName = Ledger::find($payment->ledgerId)->name ?? 'Unknown Ledger'; // Handle if ledger is not found
                    $result[$payment->blockId]['credit'][$payment->ledgerId] = [
                        'ledgerId' => $payment->ledgerId,
                        'name' => $ledgerName,
                        'amount' => 0,
                    ];
                }
                $result[$payment->blockId]['credit'][$payment->ledgerId]['amount'] += $payment->amount;
            } elseif ($payment->type === LedgerType::EXPENSE) {
                $result[$payment->blockId]['totalDebit'] += $payment->amount;
                $totalDebit += $payment->amount;  // Add to overall total
                if (!isset($result[$payment->blockId]['debit'][$payment->ledgerId])) {
                    $ledgerName = Ledger::find($payment->ledgerId)->name ?? 'Unknown Ledger'; // Handle if ledger is not found
                    $result[$payment->blockId]['debit'][$payment->ledgerId] = [
                        'ledgerId' => $payment->ledgerId,
                        'name' => $ledgerName,
                        'amount' => 0,
                    ];
                }
                $result[$payment->blockId]['debit'][$payment->ledgerId]['amount'] += $payment->amount;
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
                    'payments' => array_values($data['credit']),
                ],
                'debit' => [
                    'total' => $data['totalDebit'],
                    'payments' => array_values($data['debit']),
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
            'date' => $date
        ]);
    }
}
