<?php

namespace App\Console\Commands;

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
        $dailyTransaction = DB::table('daily_transactions')->count();
        if ($dailyTransaction == 0) {
            // Insert a new row for the next day's opening balance
            DB::table('daily_transactions')->insert([
                'opening_balance' => 0,
                'total_credit' => 0, // Store total credit separately
                'total_debit' => 0,   // Store total debit separately
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }
        // Calculate today's total debit
        $totalDebit = DB::table('payments')
            ->where('type', 'debit')
            ->whereDate('created_at', $today)
            ->sum('amount');

        // Calculate today's total credit
        $totalCredit = DB::table('payments')
            ->where('type', 'credit')
            ->whereDate('created_at', $today)
            ->sum('amount');

        $payment = DailyTransaction::whereDate('created_at', $today)->first();
        $payment->total_credit =  $totalCredit;
        $payment->total_debit =  $totalDebit;
        // dd($payment);
        $payment->update();

        // Insert a new row for the next day's opening balance
        DB::table('daily_transactions')->insert([
            'opening_balance' => $payment->opening_balance + $totalCredit - $totalDebit,
            'total_credit' => 0, // Store total credit separately
            'total_debit' => 0,   // Store total debit separately
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Opening balance for the next day set to: ' . $totalDebit);
        $this->info('Total credit for today: ' . $totalCredit);
        $this->info('Total debit for today: ' . $totalDebit);
    }
}
