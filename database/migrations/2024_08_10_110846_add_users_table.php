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
        Schema::table('users', function (Blueprint $table) {
            // $table->foreignId('courseId')->nullable();
            // $table->foreignId('mentorBatchId')->nullable();
            // $table->foreignId('studentBatchId')->nullable();
            // $table->foreignId('userFeeId')->nullable();


            // Foreign key constraints
            // $table->foreign('courseId')->references('courseID')->on('courses')->onDelete('set null');
            // $table->foreign('mentorBatchId')->references('id')->on('mentor_batches')->onDelete('set null');
            // $table->foreign('studentBatchId')->references('id')->on('student_batches')->onDelete('set null');
            // $table->foreign('userFeeId')->references('feeId')->on('user_fees')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {});
    }
};
