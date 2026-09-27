<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'low_cash_balance',
                'low_digital_balance',
                'installment_due_soon',
                'installment_overdue',
            ]);
            $table->decimal('threshold_amount', 12, 2)->nullable();
            $table->integer('days_before_due')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->json('notify_roles')->default('["owner_admin"]');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
