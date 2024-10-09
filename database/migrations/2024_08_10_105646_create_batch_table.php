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
            $table->string('name')->nullable(false);
            $table->boolean('isActive')->default(true);
            $table->boolean('isDeleted')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('time')->nullable();
            $table->unsignedBigInteger('courseId')->nullable();
            $table->foreign('courseId')->references('courseId')->on('courses')->onDelete('cascade');
            $table->unsignedBigInteger('mentorId')->nullable();
            $table->foreign('mentorId')->references('userId')->on('users')->onDelete('cascade');
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
