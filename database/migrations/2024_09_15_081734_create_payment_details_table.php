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
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id('paymentDetailsId');
            $table->timestamps();

            $table->integer('remaining_balance')->nullable();
            $table->integer('paid_balance')->nullable();
            $table->integer('total_balance_to_be_paid')->nullable();

            $table->foreignId('userId')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
