<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('opening_balance');
            $table->integer('total_credit');
            $table->integer('total_debit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_transactions');
    }
};
