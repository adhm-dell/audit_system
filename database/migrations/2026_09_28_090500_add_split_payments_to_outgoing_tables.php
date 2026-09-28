<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['expenses', 'owner_withdrawals', 'debt_payments'];
        
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('cash_amount', 12, 2)->default(0);
                $table->decimal('instapay_amount', 12, 2)->default(0);
                $table->decimal('wallet_amount', 12, 2)->default(0);
                $table->decimal('fawry_amount', 12, 2)->default(0);
            });
            
            // Migrate existing data
            $records = DB::table($tableName)->get();
            foreach ($records as $record) {
                $cash = 0;
                $instapay = 0;
                $wallet = 0;
                $fawry = 0;
                
                if (isset($record->source)) {
                    if ($record->source === 'cash') {
                        $cash = $record->amount ?? 0;
                    } elseif ($record->source === 'digital') {
                        if (isset($record->digital_channel) && $record->digital_channel === 'instapay') {
                            $instapay = $record->amount ?? 0;
                        } elseif (isset($record->digital_channel) && $record->digital_channel === 'fawry') {
                            $fawry = $record->amount ?? 0;
                        } else {
                            $wallet = $record->amount ?? 0;
                        }
                    } else {
                        $cash = $record->amount ?? 0;
                    }
                } else {
                    $cash = $record->amount ?? 0;
                }
                
                DB::table($tableName)
                    ->where('id', $record->id)
                    ->update([
                        'cash_amount' => $cash,
                        'instapay_amount' => $instapay,
                        'wallet_amount' => $wallet,
                        'fawry_amount' => $fawry,
                    ]);
            }
            
            // Make old columns nullable (if they are not already)
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('amount', 12, 2)->nullable()->change();
                $table->string('source')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $tables = ['expenses', 'owner_withdrawals', 'debt_payments'];
        
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['cash_amount', 'instapay_amount', 'wallet_amount', 'fawry_amount']);
            });
        }
    }
};
