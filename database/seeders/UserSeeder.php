<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'phoneNo' => '+9779876543210'
        ]);

        DB::table('blocks')->insert([
            ['name' => 'Block A'],
            ['name' => 'Block B'],

        ]);
    }
}
