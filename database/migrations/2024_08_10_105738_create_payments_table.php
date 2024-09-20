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
            $table->string('type')->nullable(false);
            $table->integer('amount')->nullable(false);
            // $table->unsignedBigInteger('payed_by');
            // $table->foreign('payed_by')->references('userId')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('courseId');
            $table->foreign('courseId')->references('courseId')->on('courses')->onDelete('cascade');
            $table->string('payment_mode');
            $table->string('remarks')->nullable();
            // $table->unsignedBigInteger('recieved_by');
            // $table->foreign('recieved_by')->references('userId')->on('users')->onDelete('cascade');
            $table->foreignId('payed_by');
            $table->foreignId('received_by');
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
