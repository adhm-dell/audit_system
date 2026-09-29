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

    public function saved(OwnerWithdrawal $withdrawal): void
    {
        SafeTransaction::where('reference_type', OwnerWithdrawal::class)
            ->where('reference_id', $withdrawal->id)
            ->delete();

        $snapshot = $this->balanceService->getBalanceSnapshot();

        if ((float) $withdrawal->cash_amount > 0) {
            $this->createTransaction($withdrawal, 'cash', null, (float) $withdrawal->cash_amount, $snapshot);
        }

        if ((float) $withdrawal->instapay_amount > 0) {
            $this->createTransaction($withdrawal, 'digital', 'instapay', (float) $withdrawal->instapay_amount, $snapshot);
        }

        if ((float) $withdrawal->wallet_amount > 0) {
            $this->createTransaction($withdrawal, 'digital', 'wallet', (float) $withdrawal->wallet_amount, $snapshot);
        }

        if ((float) $withdrawal->fawry_amount > 0) {
            $this->createTransaction($withdrawal, 'digital', 'fawry', (float) $withdrawal->fawry_amount, $snapshot);
        }
    }

    protected function createTransaction(OwnerWithdrawal $withdrawal, string $source, ?string $channel, float $amount, array $snapshot): void
    {
        SafeTransaction::create([
            'transaction_date' => $withdrawal->withdrawal_date,
            'amount' => $amount,
            'direction' => 'out',
            'type' => 'owner_withdrawal',
            'source' => $source,
            'digital_channel' => $channel,
            'reference_type' => OwnerWithdrawal::class,
            'reference_id' => $withdrawal->id,
            'balance_after_cash' => $snapshot['cash'],
            'balance_after_digital' => $snapshot['digital'],
        ]);
    }

    public function deleted(OwnerWithdrawal $withdrawal): void
    {
        SafeTransaction::where('reference_type', OwnerWithdrawal::class)
            ->where('reference_id', $withdrawal->id)
            ->delete();
    }
}
