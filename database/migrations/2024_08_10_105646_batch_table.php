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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mentorBatchId')->nullable()->unique();
            $table->string('batchId')->nullable(false)->unique();
            $table->boolean('isActive')->default(true);
            $table->boolean('isDeleted')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreignId('userCode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
        Schema::dropSoftDeletes();
    }
};
