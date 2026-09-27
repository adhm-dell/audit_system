<?php

namespace App\Observers;

use App\Models\DailyEnvelope;
use App\Models\SafeTransaction;
use App\Services\SafeBalanceService;

class DailyEnvelopeObserver
{
    public function __construct(
        protected SafeBalanceService $balanceService,
    ) {}

    public function saved(DailyEnvelope $envelope): void
    {
        // Remove old transactions to avoid duplicates
        $envelope->safeTransactions()->delete();

        // Record cash deposit if net_cash_to_safe > 0
        if ($envelope->net_cash_to_safe > 0) {
            $this->createTransaction($envelope, 'cash', $envelope->net_cash_to_safe);
        }

        // Record digital deposit if net_digital > 0
        if ($envelope->net_digital > 0) {
            $this->createTransaction($envelope, 'digital', $envelope->net_digital);
        }
    }

    protected function createTransaction(DailyEnvelope $envelope, string $source, float $amount): void
    {
        $snapshot = $this->balanceService->getBalanceSnapshot();

        SafeTransaction::create([
            'transaction_date' => $envelope->envelope_date,
            'type' => 'daily_deposit',
            'source' => $source,
            'direction' => 'in',
            'amount' => $amount,
            'reference_type' => DailyEnvelope::class,
            'reference_id' => $envelope->id,
            'balance_after_cash' => $snapshot['cash'],
            'balance_after_digital' => $snapshot['digital'],
        ]);
    }
}
