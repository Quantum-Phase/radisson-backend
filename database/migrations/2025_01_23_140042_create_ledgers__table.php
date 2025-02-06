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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id('ledgerId');
            $table->string('name')->nullable(false);
            $table->boolean('isStudentFeeLedger')->default(false);
            $table->boolean('isStudentRefundLedger')->default(false);

            $table->unsignedBigInteger('ledgerTypeId');
            $table->foreign('ledgerTypeId')->references('ledgerTypeId')->on('ledger_types')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger');
    }
};
