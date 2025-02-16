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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('paymentId');
            $table->enum('type', ['income', 'expense', 'liability', 'assets'])->nullable(false);
            $table->integer('amount')->nullable(false)->default(0);

            $table->unsignedBigInteger('ledgerId')->nullable();
            $table->foreign('ledgerId')->references('ledgerId')->on('ledgers')->onDelete('cascade');

            $table->unsignedBigInteger('subLedgerId')->nullable();
            $table->foreign('subLedgerId')->references('subLedgerId')->on('sub_ledgers')->onDelete('cascade');

            $table->unsignedBigInteger('batchId')->nullable();
            $table->foreign('batchId')->references('batchId')->on('batches')->onDelete('cascade');

            $table->unsignedBigInteger('blockId')->nullable();
            $table->foreign('blockId')->references('blockId')->on('blocks')->onDelete('cascade');

            $table->unsignedBigInteger('paymentModeId')->nullable();
            $table->foreign('paymentModeId')->references('paymentModeId')->on('payment_modes')->onDelete('cascade');

            $table->string('remarks')->nullable();

            $table->unsignedBigInteger('studentId')->nullable();
            $table->foreign('studentId')->references('userId')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('transaction_by')->nullable(false);
            $table->foreign('transaction_by')->references('userId')->on('users')->onDelete('cascade');

            $table->integer('due_amount')->nullable(false)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropSoftDeletes();
    }
};
