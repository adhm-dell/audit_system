<?php

namespace App\Observers;

use App\Models\BarEnvelope;
use App\Models\Debt;
use App\Models\SafeTransaction;
use App\Services\SafeBalanceService;

class BarEnvelopeObserver
{
    public function __construct(
        protected SafeBalanceService $balanceService,
    ) {}

    public function created(BarEnvelope $envelope): void
    {
        $snapshot = $this->balanceService->getBalanceSnapshot();

        if ($envelope->cash_total > 0) {
            SafeTransaction::create([
                'transaction_date' => $envelope->bar_date,
                'type' => 'bar_deposit',
                'source' => 'cash',
                'direction' => 'in',
                'amount' => $envelope->cash_total,
                'reference_type' => BarEnvelope::class,
                'reference_id' => $envelope->id,
                'balance_after_cash' => $snapshot['cash'] + $envelope->cash_total,
                'balance_after_digital' => $snapshot['digital'],
            ]);
            $snapshot['cash'] += $envelope->cash_total;
        }

        if ($envelope->network_total > 0) {
            SafeTransaction::create([
                'transaction_date' => $envelope->bar_date,
                'type' => 'bar_deposit',
                'source' => 'digital',
                'direction' => 'in',
                'amount' => $envelope->network_total,
                'reference_type' => BarEnvelope::class,
                'reference_id' => $envelope->id,
                'balance_after_cash' => $snapshot['cash'],
                'balance_after_digital' => $snapshot['digital'] + $envelope->network_total,
            ]);
            $snapshot['digital'] += $envelope->network_total;
        }

        if ($envelope->debts_total > 0 && !$envelope->debt_id) {
            $debt = Debt::create([
                'direction' => 'receivable',
                'type' => 'irregular_debt',
                'title' => "مديونيات البار - " . $envelope->bar_date->format('Y-m-d'),
                'creditor_or_debtor_name' => "عملاء البار",
                'total_amount' => $envelope->debts_total,
                'remaining_amount' => $envelope->debts_total,
                'start_date' => $envelope->bar_date,
                'status' => 'active',
            ]);

            // Save without triggering events recursively
            $envelope->debt_id = $debt->id;
            $envelope->saveQuietly();
        }
    }
}
