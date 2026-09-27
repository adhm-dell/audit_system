<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_envelopes', function (Blueprint $table) {
            $table->id();
            $table->date('envelope_date')->unique();
            $table->decimal('cash_total', 12, 2);
            $table->decimal('network_total', 12, 2);
            $table->decimal('expenses_total', 12, 2)->default(0);
            $table->enum('expenses_paid_from', ['cash', 'digital'])->default('cash');
            $table->boolean('expenses_already_deducted')->default(false);
            $table->decimal('net_cash_to_safe', 12, 2)->default(0);
            $table->decimal('net_digital', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_envelopes');
    }
};
