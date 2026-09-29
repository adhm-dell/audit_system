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

    public function saved(Expense $expense): void
    {
        SafeTransaction::where('reference_type', Expense::class)
            ->where('reference_id', $expense->id)
            ->delete();

        $snapshot = $this->balanceService->getBalanceSnapshot();

        if ((float) $expense->cash_amount > 0) {
            $this->createTransaction($expense, 'cash', null, (float) $expense->cash_amount, $snapshot);
        }

        if ((float) $expense->instapay_amount > 0) {
            $this->createTransaction($expense, 'digital', 'instapay', (float) $expense->instapay_amount, $snapshot);
        }

        if ((float) $expense->wallet_amount > 0) {
            $this->createTransaction($expense, 'digital', 'wallet', (float) $expense->wallet_amount, $snapshot);
        }

        if ((float) $expense->fawry_amount > 0) {
            $this->createTransaction($expense, 'digital', 'fawry', (float) $expense->fawry_amount, $snapshot);
        }
    }

    protected function createTransaction(Expense $expense, string $source, ?string $channel, float $amount, array $snapshot): void
    {
        SafeTransaction::create([
            'transaction_date' => $expense->expense_date,
            'amount' => $amount,
            'direction' => 'out',
            'type' => 'expense',
            'source' => $source,
            'digital_channel' => $channel,
            'reference_type' => Expense::class,
            'reference_id' => $expense->id,
            'balance_after_cash' => $snapshot['cash'],
            'balance_after_digital' => $snapshot['digital'],
        ]);
    }

    public function deleted(Expense $expense): void
    {
        SafeTransaction::where('reference_type', Expense::class)
            ->where('reference_id', $expense->id)
            ->delete();
    }
}
