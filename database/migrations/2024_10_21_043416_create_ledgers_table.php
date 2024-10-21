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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id('ledgerId');
            $table->string('name')->nullable(false);
            $table->boolean('isDefaultIncome')->default(false);
            $table->enum('type', ['income', 'expense', 'liability', 'assets'])->nullable(false);
            $table->integer('amount')->nullable(false)->default(0);
            $table->integer('openingBalance')->nullable(false)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger');
    }
};
