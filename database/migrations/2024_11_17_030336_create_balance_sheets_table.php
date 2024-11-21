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
        Schema::create('balance_sheets', function (Blueprint $table) {
            $table->id('balanceSheetId');
            $table->string('name')->nullable(false);
            $table->integer('amount')->nullable(false)->default(0);

            $table->unsignedBigInteger('assetsId')->nullable();
            $table->foreign('assetsId')->references('ledgerId')->on('ledgers')->onDelete('cascade');

            $table->unsignedBigInteger('liabilitiesId')->nullable();
            $table->foreign('liabilitiesId')->references('ledgerId')->on('ledgers')->onDelete('cascade');

            $table->string('remarks')->nullable();

            $table->unsignedBigInteger('transactionBy')->nullable();
            $table->foreign('transactionBy')->references('userId')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('blockId')->nullable();
            $table->foreign('blockId')->references('blockId')->on('blocks')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_sheets');
    }
};
