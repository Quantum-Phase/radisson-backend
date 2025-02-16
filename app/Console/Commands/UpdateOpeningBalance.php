<?php

namespace App\Console\Commands;

use App\Constants\LedgerType;
use App\Models\DailyTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;





class UpdateOpeningBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-opening-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the opening balance to the total debit at the end of the day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get today's date
        $today = Carbon::today();
        $openingBalance = 0;
        $openingCashBalance = 0;

        // Retrieve the existing daily transaction record for today
        $dailyTransaction = DailyTransaction::whereDate('created_at', $today)->first();

        if (!$dailyTransaction) {
            // Insert a new row for today's opening balance since no record exists
            DB::table('daily_transactions')->insert([
                'opening_balance' => 0,
                'opening_cash_balance' => 0,
                'total_credit' => 0,
                'total_debit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        // Calculate today's total debit and credit
        $totalDebit = DB::table('payments')
            ->where('type', LedgerType::EXPENSE)
            ->whereDate('created_at', $today)
            ->sum('amount');


        $totalCredit = DB::table('payments')
            ->where('type', LedgerType::INCOME)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $totalCashDebit = DB::table('payments')
            ->where('payments.type', LedgerType::EXPENSE)
            ->join('payment_modes', 'payments.paymentModeId', '=', 'payment_modes.paymentModeId')
            ->where('payment_modes.type', 'cash')
            ->whereDate('payments.created_at', $today)
            ->sum('amount');

        $totalCashCredit = DB::table('payments')
            ->where('payments.type', LedgerType::INCOME)
            ->join('payment_modes', 'payments.paymentModeId', '=', 'payment_modes.paymentModeId')
            ->where('payment_modes.type', 'cash')
            ->whereDate('payments.created_at', $today)
            ->sum('amount');

        $dailyTransaction->total_credit = $totalCredit;
        $dailyTransaction->total_debit = $totalDebit;
        $dailyTransaction->update();
        // Insert a new row for the next day's opening balance
        DB::table('daily_transactions')->insert([
            'opening_balance' => $openingBalance + $totalCredit - $totalDebit,
            'opening_cash_balance' => $openingCashBalance + $totalCashCredit - $totalCashDebit,
            'total_credit' => $totalCredit, // Store total credit separately
            'total_debit' => $totalDebit,   // Store total debit separately
            'created_at' => Carbon::tomorrow(),
            'updated_at' => Carbon::tomorrow(),
        ]);

        $this->info('Opening balance for the next day set to: ' . ($openingBalance + $totalCredit - $totalDebit));
        $this->info('Opening cash balance for the next day set to: ' . ($openingCashBalance + $totalCashCredit - $totalCashDebit));
        $this->info('Total credit for today: ' . $totalCredit);
        $this->info('Total debit for today: ' . $totalDebit);
    }
}
