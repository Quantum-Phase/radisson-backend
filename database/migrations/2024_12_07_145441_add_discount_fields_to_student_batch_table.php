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
        Schema::table('student_batches', function (Blueprint $table) {
            $table->string('discountType')->nullable(true);
            $table->integer('discountAmount')->nullable(true);
            $table->integer('discountPercent')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_batches', function (Blueprint $table) {
            $table->dropColumn('discountType');
            $table->dropColumn('discountAmount');
            $table->dropColumn('discountPercent');
        });
    }
};
