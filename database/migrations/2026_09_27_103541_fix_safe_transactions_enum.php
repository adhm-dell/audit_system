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
        Schema::table('safe_transactions', function (Blueprint $table) {
            $table->string('type')->change();
        });

        Schema::table('safe_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'daily_deposit', 
                'bar_deposit', 
                'owner_withdrawal', 
                'owner_deposit',
                'expense', 
                'debt_payment'
            ])->change();
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
