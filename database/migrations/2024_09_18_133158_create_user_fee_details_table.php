<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_fee_details', function (Blueprint $table) {
            $table->id('userFeeDetailId');
            $table->unsignedBigInteger('userId');
            $table->foreign('userId')->references('userId')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('batchId');
            $table->foreign('batchId')->references('batchId')->on('batches')->onDelete('cascade');
            $table->integer('amountToBePaid')->default(0);
            $table->integer('totalAmountPaid')->default(0);
            $table->integer('remainingAmount')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('
            CREATE TRIGGER update_remaining_amount
            BEFORE UPDATE ON user_fee_details
            FOR EACH ROW
            BEGIN
                IF NEW.amountToBePaid > NEW.totalAmountPaid THEN
                    SET NEW.remainingAmount = NEW.amountToBePaid - NEW.totalAmountPaid;
                ELSE
                    SET NEW.remainingAmount = 0;
                END IF;
            END;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_fee_details');
    }
};
