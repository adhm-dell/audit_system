<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safe_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('type', ['daily_deposit', 'owner_withdrawal', 'capital_expense', 'debt_payment']);
            $table->enum('source', ['cash', 'digital']);
            $table->enum('direction', ['in', 'out']);
            $table->decimal('amount', 12, 2);
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->decimal('balance_after_cash', 12, 2);
            $table->decimal('balance_after_digital', 12, 2);
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safe_transactions');
    }
};
