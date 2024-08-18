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
        Schema::create('courses', function (Blueprint $table) {
            $table->id('courseId');
            $table->string('name')->nullable(false);
            $table->boolean('isActive')->default(true);
            $table->boolean('isDeleted')->default(false);
            $table->decimal('totalFee', 8, 2);
            $table->string('duration_unit');
            $table->integer('duration');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
        Schema::dropSoftDeletes();
    }
};
