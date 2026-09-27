<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\SafeTransaction;
use App\Services\SafeBalanceService;

class ExpenseObserver
{
    public function __construct(
        protected SafeBalanceService $balanceService,
    ) {}

    public function created(Expense $expense): void
    {
        $snapshot = $this->balanceService->getBalanceSnapshot();

        SafeTransaction::create([
            'transaction_date' => $expense->expense_date,
            'type' => 'expense',
            'source' => $expense->source,
            'direction' => 'out',
            'amount' => $expense->amount,
            'reference_type' => Expense::class,
            'reference_id' => $expense->id,
            'balance_after_cash' => $snapshot['cash'],
            'balance_after_digital' => $snapshot['digital'],
        ]);
    }
}
