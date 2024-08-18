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
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phoneNo');
            $table->date('dob');
            $table->string('gender');
            $table->string('profileImg')->nullable();
            $table->string('userCode')->unique();
            $table->string('role');
            $table->string('permanentAddress');
            $table->string('temporaryAddress')->nullable();
            $table->date('createdAt');
            $table->date('startDate');
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
