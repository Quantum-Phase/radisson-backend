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
        Schema::create('user_payments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreignId('userId');
            $table->foreignId('paymentId');
            $table->foreignId('batchId');
            // $table->foreignId('courseId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payments');
        Schema::dropSoftDeletes();
    }
};
