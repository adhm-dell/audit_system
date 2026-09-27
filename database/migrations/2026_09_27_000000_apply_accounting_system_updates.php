<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create employees table first
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('role', ['staff', 'trainer', 'reception', 'cleaner', 'worker', 'other']);
            $table->decimal('monthly_salary', 12, 2)->nullable();
            $table->string('phone')->nullable();
            $table->date('hire_date');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Rename capital_expenses to expenses and add columns
        Schema::rename('capital_expenses', 'expenses');
        Schema::table('expenses', function (Blueprint $table) {
            // First we drop the old enum and recreate if we can't change it smoothly, but ->change() works in modern Laravel
            // However, Doctrine DBAL sometimes struggles with enum changes. In Laravel 11+ it uses native schema builder.
            // But just to be safe with string type casting
            $table->string('category')->change();
        });
        Schema::table('expenses', function (Blueprint $table) {
            // We'll leave it as string to avoid enum issues, or redefine as enum. We will redefine as enum:
            $table->enum('category', [
                'construction','equipment','maintenance','renovation','utilities',
                'worker_wages','employee_advance','salary','operational','other'
            ])->change();
            
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('digital_channel', ['instapay', 'wallet', 'fawry'])->nullable();
        });

        // 3. Add digital_channel and network breakdown to daily_envelopes
        Schema::table('daily_envelopes', function (Blueprint $table) {
            $table->enum('digital_channel', ['instapay', 'wallet', 'fawry'])->nullable();
            $table->decimal('network_instapay_total', 12, 2)->default(0)->after('cash_total');
            $table->decimal('network_wallet_total', 12, 2)->default(0)->after('network_instapay_total');
            $table->decimal('network_fawry_total', 12, 2)->default(0)->after('network_wallet_total');
        });

        // 4. Add digital_channel to owner_withdrawals
        Schema::table('owner_withdrawals', function (Blueprint $table) {
            $table->enum('digital_channel', ['instapay', 'wallet', 'fawry'])->nullable();
        });

        // 5. Add digital_channel to debt_payments
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->enum('digital_channel', ['instapay', 'wallet', 'fawry'])->nullable();
        });

        // 6. Create daily_envelope_items table
        Schema::create('daily_envelope_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_envelope_id')->constrained('daily_envelopes')->cascadeOnDelete();
            $table->enum('category', ['worker_wages', 'employee_advance', 'utilities', 'operational', 'other']);
            $table->decimal('amount', 12, 2);
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('paid_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Create bar_envelopes table
        Schema::create('bar_envelopes', function (Blueprint $table) {
            $table->id();
            $table->date('bar_date')->unique();
            $table->decimal('cash_total', 12, 2)->default(0);
            $table->decimal('network_instapay_total', 12, 2)->default(0);
            $table->decimal('network_wallet_total', 12, 2)->default(0);
            $table->decimal('network_fawry_total', 12, 2)->default(0);
            $table->decimal('network_total', 12, 2)->default(0);
            $table->decimal('debts_total', 12, 2)->default(0);
            $table->foreignId('debt_id')->nullable()->constrained('debts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Update safe_transactions type enum
        DB::table('safe_transactions')->where('type', 'capital_expense')->update(['type' => 'expense']);
        Schema::table('safe_transactions', function (Blueprint $table) {
            $table->string('type')->change();
        });
        Schema::table('safe_transactions', function (Blueprint $table) {
            $table->enum('type', ['daily_deposit', 'bar_deposit', 'owner_withdrawal', 'expense', 'debt_payment'])->change();
        });
    }

    public function down(): void
    {
        // Not implementing down for this complex transition as requested (non-destructive)
        // But for safe rollback we would reverse the operations.
    }
};
