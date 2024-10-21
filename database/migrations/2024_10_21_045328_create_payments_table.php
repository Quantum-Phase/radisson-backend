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
            $table->string('name')->nullable(false);
            $table->enum('type', ['income', 'expense', 'liability', 'assets'])->nullable(false);
            $table->integer('amount')->nullable(false)->default(0);

            $table->unsignedBigInteger('ledgerId')->nullable();
            $table->foreign('ledgerId')->references('ledgerId')->on('ledgers')->onDelete('cascade');

            $table->unsignedBigInteger('batchId')->nullable();
            $table->foreign('batchId')->references('batchId')->on('batches')->onDelete('cascade');

            $table->unsignedBigInteger('blockId')->nullable();
            $table->foreign('blockId')->references('blockId')->on('blocks')->onDelete('cascade');

            $table->unsignedBigInteger('paymentModeId')->nullable();
            $table->foreign('paymentModeId')->references('paymentModeId')->on('payment_modes')->onDelete('cascade');

            $table->string('remarks')->nullable();

            $table->foreignId('payed_by')->nullable();
            $table->foreignId('transaction_by')->nullable(false);

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
