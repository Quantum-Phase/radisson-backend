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
        Schema::create('user_fees', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreignId('userCode');

            $table->foreignId('feeId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_fees');
        Schema::dropSoftDeletes();
    }
};
