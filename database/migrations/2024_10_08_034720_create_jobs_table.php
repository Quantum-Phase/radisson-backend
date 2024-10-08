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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id('jobId');

            $table->unsignedBigInteger('companyId');
            $table->foreign('companyId')->references('companyId')->on('company');

            $table->unsignedBigInteger('departmentId');
            $table->foreign('departmentId')->references('departmentId')->on('departments');

            $table->unsignedBigInteger('studentId');
            $table->foreign('studentId')->references('userId')->on('users');

            $table->integer('paid_amount')->nullable();
            $table->date('start_date')->nullable()->format('Y-m-d');
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
        Schema::dropIfExists('jobs');
    }
};
