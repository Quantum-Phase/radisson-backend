<?php

namespace App\Http\Controllers;

use App\Constants\LedgerType;
use App\Models\Block;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\SubLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyTransactionController extends Controller
{
    public function showTransaction(Request $request)
    {

        // Initialize totals
        $openingBalance = 0;
        $openingCashBalance = 0;
        $totalCredit = 0;
        $totalDebit = 0;
        $totalCashDebit = 0;
        $totalCashCredit = 0;
        $result = [];

        $user = auth()->user();
        $blockId = $request->blockId;
        $transactionBy = $request->transactionBy;

        $date = $request->date ? Carbon::parse($request->date)->startOfDay() : now()->startOfDay();

        $openingblnc = DB::table('daily_transactions')->whereDate('created_at', $date)->first();
        if ($openingblnc) {
            $openingBalance = $openingblnc->opening_balance;
            $openingCashBalance = $openingblnc->opening_cash_balance;
        }

        // Fetch payments made today
        $paymentsQuery = Payment::whereDate('created_at', '=', $date)->whereIn('type', [LedgerType::INCOME, LedgerType::EXPENSE]);

        if ($user->role !== 'superadmin') {
            $paymentsQuery->where('blockId', $user->blockId)->where('transaction_by', $user->userId);
        } else if ($blockId && $blockId !== 'all') {
            $paymentsQuery->where('blockId', $blockId);
        } else {
            $paymentsQuery->orderBy('blockId', 'asc');
        }

        // Add transaction by filter if provided
        if ($transactionBy) {
            $paymentsQuery->where('transaction_by', $transactionBy);
        }

        // Get all payments
        $payments = $paymentsQuery
            ->orderBy('created_at', 'desc')
            ->with(['student', 'paymentMode', 'subLedger'])
            ->get(['paymentId', 'type', 'amount', 'blockId', 'ledgerId', 'studentId', 'paymentModeId', 'subLedgerId']);

        // If no payments found, return empty response
        if ($payments->isEmpty()) {
            return response()->json([
                'openingBalance' => $openingBalance,
                'openingCashBalance' => $openingCashBalance,
                'totalCredit' => 0,
                'totalDebit' => 0,
                'totalCashDebit' => 0,
                'totalCashCredit' => 0,
                'data' => [],
                'date' => $date
            ]);
        }

        // Calculate cash totals using collection methods
        $totalCashDebit = $payments
            ->filter(function ($payment) {
                return $payment->paymentMode
                    && $payment->paymentMode->type === 'cash'
                    && $payment->type === LedgerType::EXPENSE;
            })
            ->sum('amount');

        $totalCashCredit = $payments
            ->filter(function ($payment) {
                return $payment->paymentMode
                    && $payment->paymentMode->type === 'cash'
                    && $payment->type === LedgerType::INCOME;
            })
            ->sum('amount');

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
                        'id' => $payment->ledgerId,
                        'name' => $ledgerName,
                        'amount' => 0,
                        'transactions' => [] // Add array to store individual transactions
                    ];
                }
                $result[$payment->blockId]['credit'][$payment->ledgerId]['amount'] += $payment->amount;

                // Add individual transaction details
                $result[$payment->blockId]['credit'][$payment->ledgerId]['transactions'][] = [
                    'id' => $payment->paymentId,
                    'amount' => $payment->amount,
                    'name' => $payment->student ? $payment->student->name : 'N/A',
                    'paymentMode' => $payment->paymentMode ? $payment->paymentMode->name : 'N/A'
                ];
            } elseif ($payment->type === LedgerType::EXPENSE) {
                $result[$payment->blockId]['totalDebit'] += $payment->amount;
                $totalDebit += $payment->amount;  // Add to overall total

                // Group by payment mode for debit transactions
                $paymentModeId = $payment->paymentModeId;
                $paymentMode = $payment->paymentMode;

                // Check if it's a cash payment
                if ($paymentMode && $paymentMode->type === 'cash') {
                    $paymentModeId = 'cash'; // Use a fixed key for all cash transactions
                    $paymentModeName = 'Cash';
                } else {
                    $paymentModeName = $paymentMode->name ?? 'Unknown Payment Mode';
                }

                if (!isset($result[$payment->blockId]['debit'][$paymentModeId])) {
                    $result[$payment->blockId]['debit'][$paymentModeId] = [
                        'id' => $paymentModeId,
                        'name' => $paymentModeName,
                        'amount' => 0,
                        'transactions' => [] // Only store transactions for non-cash payments
                    ];
                }

                // Add amount to payment mode total
                $result[$payment->blockId]['debit'][$paymentModeId]['amount'] += $payment->amount;

                // Add transaction details for all payments (cash and non-cash)
                $result[$payment->blockId]['debit'][$paymentModeId]['transactions'][] = [
                    'id' => $payment->ledgerId,
                    'name' => $payment->subLedger ? $payment->subLedger->name : 'Unknown Sub Ledger',
                    'amount' => $payment->amount,
                ];
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
                    'payments' => array_values($data['debit']), // Now grouped by payment mode
                ],
            ];
        }
        // dd($openingblnc);

        // Return results or pass them to a view
        return response()->json([
            'openingBalance' => $openingBalance,
            'openingCashBalance' => $openingCashBalance,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'totalCashDebit' => $totalCashDebit,
            'totalCashCredit' => $totalCashCredit,
            'data' => $finalResult,
            'date' => $date
        ]);
    }
}
