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
        Schema::create('accountant_blocks', function (Blueprint $table) {
            $table->id('accountBlockId');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('userId')->nullable(false);
            $table->foreign('blockId')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accountant_blocks');
    }
};