<?php

namespace App\Observers;

use App\Models\DebtPayment;
use App\Models\SafeTransaction;
use App\Services\DebtService;
use App\Services\SafeBalanceService;

class DebtPaymentObserver
{
    public function __construct(
        protected SafeBalanceService $balanceService,
        protected DebtService $debtService,
    ) {}

    public function saved(DebtPayment $payment): void
    {
        SafeTransaction::where('reference_type', DebtPayment::class)
            ->where('reference_id', $payment->id)
            ->delete();

        $debt = $payment->debt;
        $direction = $debt->direction === 'payable' ? 'out' : 'in';

        $snapshot = $this->balanceService->getBalanceSnapshot();

        if ((float) $payment->cash_amount > 0) {
            $this->createTransaction($payment, 'cash', null, (float) $payment->cash_amount, $direction, $snapshot);
        }

        if ((float) $payment->instapay_amount > 0) {
            $this->createTransaction($payment, 'digital', 'instapay', (float) $payment->instapay_amount, $direction, $snapshot);
        }

        if ((float) $payment->wallet_amount > 0) {
            $this->createTransaction($payment, 'digital', 'wallet', (float) $payment->wallet_amount, $direction, $snapshot);
        }

        if ((float) $payment->fawry_amount > 0) {
            $this->createTransaction($payment, 'digital', 'fawry', (float) $payment->fawry_amount, $direction, $snapshot);
        }

        if ($payment->installment_id) {
            $this->debtService->updateInstallmentAfterPayment($payment->installment);
        }

        $this->debtService->recalculateDebtStatus($debt);
    }

    protected function createTransaction(DebtPayment $payment, string $source, ?string $channel, float $amount, string $direction, array $snapshot): void
    {
        SafeTransaction::create([
            'transaction_date' => $payment->payment_date,
            'amount' => $amount,
            'direction' => $direction,
            'type' => 'debt_payment',
            'source' => $source,
            'digital_channel' => $channel,
            'reference_type' => DebtPayment::class,
            'reference_id' => $payment->id,
            'balance_after_cash' => $snapshot['cash'],
            'balance_after_digital' => $snapshot['digital'],
        ]);
    }

    public function deleted(DebtPayment $payment): void
    {
        SafeTransaction::where('reference_type', DebtPayment::class)
            ->where('reference_id', $payment->id)
            ->delete();

        if ($payment->installment_id) {
            $this->debtService->updateInstallmentAfterPayment($payment->installment);
        }

        $this->debtService->recalculateDebtStatus($payment->debt);
    }
}
