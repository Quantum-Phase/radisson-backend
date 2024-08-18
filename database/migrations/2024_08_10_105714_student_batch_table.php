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
            $table->foreignId('userCode');
            $table->foreignId('batchId');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints


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
