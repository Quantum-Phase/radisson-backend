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
        Schema::create('mentor_batches', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreignId('userId');
            $table->foreignId('batchId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentor_batches');
        Schema::dropSoftDeletes();
    }
};
