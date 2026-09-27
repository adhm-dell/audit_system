<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->enum('direction', ['payable', 'receivable']);
            $table->enum('type', ['loan', 'installment_debt', 'irregular_debt']);
            $table->string('title');
            $table->string('creditor_or_debtor_name');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['active', 'paid', 'overdue', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
