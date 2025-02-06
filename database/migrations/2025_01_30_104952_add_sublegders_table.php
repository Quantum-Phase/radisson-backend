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
        Schema::create('sub_ledgers', function (Blueprint $table) {
            $table->id('subLedgerId');
            $table->string('name')->nullable(false);

            $table->unsignedBigInteger('ledgerId');
            $table->foreign('ledgerId')->references('ledgerId')->on('ledgers')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_ledgers');
    }
};
