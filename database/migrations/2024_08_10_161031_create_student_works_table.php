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
        Schema::create('student_works', function (Blueprint $table) {
            $table->id('workId');
            $table->string('name')->nullable(false);
            $table->date('start_date')->nullable();
            $table->string('type');
            $table->boolean('isActive')->default(true);
            $table->boolean('isDeleted')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
