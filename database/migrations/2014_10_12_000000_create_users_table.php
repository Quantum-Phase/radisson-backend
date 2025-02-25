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
        Schema::create('users', function (Blueprint $table) {
            $table->id('userId');
            $table->string('name');
            $table->string('student_code')->unique()->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('phoneNo');
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('profileImg')->nullable();
            $table->string('role');
            $table->string('permanentAddress')->nullable();
            $table->string('temporaryAddress')->nullable();
            $table->string('emergencyContactNo')->nullable();
            $table->date('startDate')->nullable();
            $table->string('parents_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropSoftDeletes();
    }
};
