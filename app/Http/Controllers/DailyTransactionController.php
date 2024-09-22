<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;




class DailyTransactionController extends Controller
{
    public function showTransaction(Request $request)
    {
        // Initialize totals and groups
        $openingBalance = 0;
        $totalCredit = 0;
        $totalDebit = 0;
        $creditGroup = [];
        $debitGroup = [];
        $date = now();

        // Fetch all payments
        $payments = Payment::whereDate('created_at', $date->format('Y-m-d'))->get();

        // Process payments and group by type
        foreach ($payments as $payment) {
            if (isset($payment->type)) {
                if ($payment->type == 'credit') {
                    $totalCredit += $payment->amount;
                    $creditGroup[] = $payment;
                } elseif ($payment->type == 'debit') {
                    $totalDebit += $payment->amount;
                    $debitGroup[] = $payment;
                }
            }
        }

        // Fetch grouped credit data from the database
        $creditQuery = DB::table('payments')
            ->join('blocks', 'payments.blockId', '=', 'blocks.blockId')
            ->select(
                'payments.blockId',
                'blocks.name as blockName',
                DB::raw('SUM(payments.amount) as totalAmount')
            )
            ->where('payments.type', 'credit') // Filter for credits
            ->where(DB::raw("DATE(payments.created_at)"), '=', $date->format('Y-m-d'))
            ->groupBy('payments.blockId', 'blocks.name')
            ->get()
            ->map(function ($item) {
                return [
                    'blockId' => $item->blockId,
                    'blockName' => $item->blockName,
                    'totalAmount' => $item->totalAmount,
                    'paymentData' => DB::table('payments')
                        ->select('paymentId', 'name', 'type', 'amount', 'created_at')
                        ->where('blockId', $item->blockId)
                        ->where(DB::raw("DATE(payments.created_at)"), '=', now()->format('Y-m-d'))
                        ->where('type', 'credit') // Get credit payment data for this block
                        ->get(),
                ];
            });

        // Fetch grouped debit data from the database
        $debitQuery = DB::table('payments')
            ->join('blocks', 'payments.blockId', '=', 'blocks.blockId')
            ->select(
                'payments.blockId',
                'blocks.name as blockName',
                DB::raw('SUM(payments.amount) as totalAmount')
            )
            ->where('payments.type', 'debit') // Filter for debits
            ->where(DB::raw("DATE(payments.created_at)"), '=', $date->format('Y-m-d'))
            ->groupBy('payments.blockId', 'blocks.name')
            ->get()
            ->map(function ($item) {
                return [
                    'blockId' => $item->blockId,
                    'blockName' => $item->blockName,
                    'totalAmount' => $item->totalAmount,
                    'paymentData' => DB::table('payments')
                        ->select('paymentId', 'name', 'type', 'amount', 'created_at')
                        ->where('blockId', $item->blockId)
                        ->where(DB::raw("DATE(payments.created_at)"), '=', now()->format('Y-m-d'))
                        ->where('type', 'debit') // Get debit payment data for this block
                        ->get(),
                ];
            });

        // Return results or pass them to a view
        return response()->json([
            'openingBalance' => $openingBalance,
            'totalCredit' => $totalCredit,
            'totalDebit' => $totalDebit,
            'creditData' => $creditQuery,
            'debitData' => $debitQuery,
            'date' => $date
        ]);
    }


    public function insertTransaction(Request $request) {}
}
