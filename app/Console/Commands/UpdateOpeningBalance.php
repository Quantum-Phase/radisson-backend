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
        // Initialize opening_balance and total_credit_total_debit to 0
        $initialBalance = [
            'opening_balance' => 0,
            'total_credit' => 0,
            'total_debit' => 0,
        ];

        // Get today's date
        $today = Carbon::today();

        // Retrieve the existing daily transaction record for today
        $payment = DailyTransaction::whereDate('created_at', $today)->first();

        if (!$payment) {
            // Insert a new row for today's opening balance since no record exists
            DB::table('daily_transactions')->insert([
                'opening_balance' => 0,
                'total_credit' => 0,
                'total_debit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // After inserting, retrieve the newly created record
            // $payment = DailyTransaction::whereDate('created_at', $today)->first();
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


        // Update the existing or new daily transaction record
        $payment->total_credit = $totalCredit;
        $payment->total_debit = $totalDebit;
        $payment->update();

        // Insert a new row for the next day's opening balance
        DB::table('daily_transactions')->insert([
            'opening_balance' => $payment->opening_balance + $totalCredit - $totalDebit,
            'total_credit' => 0, // Store total credit separately
            'total_debit' => 0,   // Store total debit separately
            'created_at' => Carbon::tomorrow(),
            'updated_at' => Carbon::tomorrow(),
        ]);

        $this->info('Opening balance for the next day set to: ' . ($payment->opening_balance + $totalCredit - $totalDebit));
        $this->info('Total credit for today: ' . $totalCredit);
        $this->info('Total debit for today: ' . $totalDebit);
    }
}
