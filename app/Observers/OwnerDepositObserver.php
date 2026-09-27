<?php

namespace App\Observers;

use App\Models\OwnerDeposit;
use App\Models\SafeTransaction;
use App\Services\SafeBalanceService;

class OwnerDepositObserver
{
    public function __construct(
        protected SafeBalanceService $balanceService,
    ) {}

    public function saved(OwnerDeposit $deposit): void
    {
        // Remove old transactions to avoid duplicates when updating
        SafeTransaction::where('reference_type', OwnerDeposit::class)
            ->where('reference_id', $deposit->id)
            ->delete();

        $snapshot = $this->balanceService->getBalanceSnapshot();

        if ($deposit->cash_amount > 0) {
            $this->createTransaction($deposit, 'cash', null, $deposit->cash_amount, $snapshot);
        }

        if ($deposit->instapay_amount > 0) {
            $this->createTransaction($deposit, 'digital', 'instapay', $deposit->instapay_amount, $snapshot);
        }

        if ($deposit->wallet_amount > 0) {
            $this->createTransaction($deposit, 'digital', 'wallet', $deposit->wallet_amount, $snapshot);
        }

        if ($deposit->fawry_amount > 0) {
            $this->createTransaction($deposit, 'digital', 'fawry', $deposit->fawry_amount, $snapshot);
        }
    }

    protected function createTransaction(OwnerDeposit $deposit, string $source, ?string $channel, float $amount, array $snapshot): void
    {
        SafeTransaction::create([
            'transaction_date' => $deposit->deposit_date,
            'amount' => $amount,
            'direction' => 'in',
            'type' => 'owner_deposit',
            'source' => $source,
            'digital_channel' => $channel,
            'reference_type' => OwnerDeposit::class,
            'reference_id' => $deposit->id,
            'balance_after_cash' => $snapshot['cash'],
            'balance_after_digital' => $snapshot['digital'],
            'description' => 'إيداع من شريك / رصيد ابتدائي',
        ]);
    }

    public function deleted(OwnerDeposit $deposit): void
    {
        SafeTransaction::where('reference_type', OwnerDeposit::class)
            ->where('reference_id', $deposit->id)
            ->delete();
    }
}
