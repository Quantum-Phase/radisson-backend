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
        Schema::table('user_fee_details', function (Blueprint $table) {
            $table->integer('refundRequestedAmount')->default(0);
            $table->integer('refundedAmount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_fee_details', function (Blueprint $table) {
            $table->dropColumn('refundRequestedAmount');
            $table->dropColumn('refundedAmount');
        });
    }
};
