<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Ledger;
use App\Models\SubLedger;
use App\Models\User;
use App\Models\Batch;
use App\Models\Block;   

class ReportController extends Controller
{
    public function getReports(Request $request)
    {
        $payments = Payment::all();

        $ledgerReports = [];
        $subLedgerReports = [];

        foreach ($payments as $payment) {
            $ledger = $payment->ledger;
            $subLedger = $payment->subLedger;
            $student = User::find($payment->studentId);
            $batch= Batch::find($payment->batchId);
            $block = Block::find($payment->blockId);
            $transactionBy= User::find($payment->transaction_by);
            if ($ledger && $subLedger) {
                $ledgerReport = [
                    'ledgerId' => $ledger->ledgerId,
                    'name' => $ledger->name,
                    'amount' => $payment->amount,
                    'studentId' => $payment->studentId,
                    'studentName' => $student ? $student->name : null,
                    'type' => $ledger->ledgerType->type,
                    'batchName'=>$batch ? $batch->name : null,
                    'blockName'=>$block ? $block->name : null,
                    'subledgerId' => $subLedger->subLedgerId,
                    'subLedgerName' => $subLedger->name,
                    'paymentmode' => $payment->paymentMode->name,
                    'transactionBy'=>$transactionBy ? $transactionBy->name : null
                ];

                $ledgerReports[] = $ledgerReport;
            }

            // if ($subLedger) {
            //     $subLedgerReport = [
            //         'subLedgerId' => $subLedger->subLedgerId,
            //         'name' => $subLedger->name,
            //         'amount' => $payment->amount,
            //     ];

            //     $subLedgerReports[] = $subLedgerReport;
            // }
        }

        return response()->json([
            'ledgerReports' => $ledgerReports,
            // 'subLedgerReports' => $subLedgerReports,
        ]);
    }
}
