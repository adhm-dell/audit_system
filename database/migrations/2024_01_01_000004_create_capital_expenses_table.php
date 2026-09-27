<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capital_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->string('title');
            $table->enum('category', ['construction', 'equipment', 'maintenance', 'renovation', 'other']);
            $table->decimal('amount', 12, 2);
            $table->enum('source', ['cash', 'digital']);
            $table->string('paid_to')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capital_expenses');
    }
};
