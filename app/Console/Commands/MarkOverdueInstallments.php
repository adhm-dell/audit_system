<?php

namespace App\Console\Commands;

use App\Models\Debt;
use App\Models\DebtInstallment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkOverdueInstallments extends Command
{
    protected $signature = 'installments:mark-overdue';
    protected $description = 'Mark overdue installments and debts that have passed their due date';

    public function handle(): int
    {
        $today = Carbon::today();

        // Mark overdue installments
        $installmentsUpdated = DebtInstallment::where('due_date', '<', $today)
            ->whereIn('status', ['pending', 'partial'])
            ->update(['status' => 'overdue']);

        $this->info("Marked {$installmentsUpdated} installments as overdue.");

        // Mark overdue debts
        $debtsUpdated = Debt::where('due_date', '<', $today)
            ->where('status', 'active')
            ->update(['status' => 'overdue']);

        $this->info("Marked {$debtsUpdated} debts as overdue.");

        return self::SUCCESS;
    }
}
