<?php

namespace App\Observers;

use App\Models\OwnerWithdrawal;
use App\Models\SafeTransaction;
use App\Services\SafeBalanceService;

class OwnerWithdrawalObserver
{
    public function __construct(
        protected SafeBalanceService $balanceService,
    ) {}

    public function created(OwnerWithdrawal $withdrawal): void
    {
        $snapshot = $this->balanceService->getBalanceSnapshot();

        SafeTransaction::create([
            'transaction_date' => $withdrawal->withdrawal_date,
            'type' => 'owner_withdrawal',
            'source' => $withdrawal->source,
            'direction' => 'out',
            'amount' => $withdrawal->amount,
            'reference_type' => OwnerWithdrawal::class,
            'reference_id' => $withdrawal->id,
            'balance_after_cash' => $snapshot['cash'],
            'balance_after_digital' => $snapshot['digital'],
        ]);
    }
}
