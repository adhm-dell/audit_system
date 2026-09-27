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
        Schema::table('owner_deposits', function (Blueprint $table) {
            $table->dropColumn(['amount', 'source', 'digital_channel']);
            $table->decimal('cash_amount', 12, 2)->default(0)->after('owner_id');
            $table->decimal('instapay_amount', 12, 2)->default(0)->after('cash_amount');
            $table->decimal('wallet_amount', 12, 2)->default(0)->after('instapay_amount');
            $table->decimal('fawry_amount', 12, 2)->default(0)->after('wallet_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_deposits', function (Blueprint $table) {
            $table->dropColumn(['cash_amount', 'instapay_amount', 'wallet_amount', 'fawry_amount']);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('source', ['cash', 'digital'])->default('cash');
            $table->string('digital_channel')->nullable();
        });
    }
};
