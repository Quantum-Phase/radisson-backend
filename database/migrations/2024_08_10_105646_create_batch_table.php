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
            $table->id('batchId');
            $table->string('name')->nullable(false)->unique();
            $table->boolean('isActive')->default(true);
            $table->boolean('isDeleted')->default(false);

            // Add start_date and time fields
            $table->date('start_date')->nullable(); // To store the class start date
            $table->time('time')->nullable(); // To store the class time

            $table->timestamps();
            $table->softDeletes();
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
