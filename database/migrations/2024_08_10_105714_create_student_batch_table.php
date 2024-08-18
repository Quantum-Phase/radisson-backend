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
        Schema::create('student_batches', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints

            $table->foreignId('userId');
            // $table->unsignedBigInteger('userId'); // Define the userId column to match the type in the `users` table
            // $table->foreign('userId') // Define the foreign key constraint
            //     ->references('userId')
            //     ->on('users')
            //     ->onDelete('cascade');
            $table->foreignId('batchId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_batches');
        Schema::dropSoftDeletes();
    }
};
