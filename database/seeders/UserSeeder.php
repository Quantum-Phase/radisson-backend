<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Radisson',
            'email' => 'radisson@gmail.com',
            'student_code' => "RadAdmin",
            'password' => Hash::make('Aadmin@123'),
            'role' => 'superadmin',
            'phoneNo' => '+9779876543210',
            'blockId' => null
        ]);

        DB::table('blocks')->insert([
            ['name' => 'Block A'],
            ['name' => 'Block B'],
        ]);

        DB::table('ledger_types')->insert([
            ['name' => 'Bank Accounts', 'type' =>  'assets'],
            ['name' => 'Cash', 'type' => 'assets'],
            ['name' => 'Current Assets', 'type' => 'assets'],
            ['name' => 'Fixed Assets', 'type' => 'assets'],
            ['name' => 'Investment', 'type' => 'assets'],
            ['name' => 'Stock', 'type' => 'assets'],
            ['name' => 'Receivable', 'type' => 'assets'],

            ['name' => 'Capital Account', 'type' => 'liability'],
            ['name' => 'Current Liability', 'type' => 'liability'],
            ['name' => 'Payable', 'type' => 'liability'],
            ['name' => 'Loan', 'type' => 'liability'],
            ['name' => 'Reserve & Surplus', 'type' => 'liability'],
            ['name' => 'Profit to till date', 'type' => 'liability'],
            ['name' => 'Tax Payable', 'type' => 'liability'],

            ['name' => 'Student Fee', 'type' => 'income'],
            ['name' => 'Indirect Income', 'type' => 'income'],

            ['name' => 'Direct Expenses', 'type' => 'expense'],
            ['name' => 'Indirect Expenses', 'type' => 'expense'],
            ['name' => 'Fee Refund', 'type' => 'expense'],
        ]);

        DB::table('payment_modes')->insert([
            ['name' => 'Cash', 'isDefault' => true],
        ]);

        // Fetch the ID of 'Student Fee' from the LedgerType table
        $ledgerTypeId = DB::table('ledger_types')->where('name', 'Student Fee')->value('ledgerTypeId');

        // Insert into the Ledger table
        DB::table('ledgers')->insert([
            'name' => 'Student Fee',
            'ledgerTypeId' => $ledgerTypeId,
            'isStudentFeeLedger' => true
        ]);

        // Fetch the ID of 'Student Fee' from the LedgerType table
        $ledgerTypeId = DB::table('ledger_types')->where('name', 'Fee Refund')->value('ledgerTypeId');

        // Insert into the Ledger table
        DB::table('ledgers')->insert([
            'name' => 'Student Refund',
            'ledgerTypeId' => $ledgerTypeId,
            'isStudentRefundLedger' => true
        ]);
    }
}
