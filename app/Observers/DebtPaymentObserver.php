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

    public function created(DebtPayment $payment): void
    {
        $debt = $payment->debt;

        // Determine direction: payable = money going out, receivable = money coming in
        $direction = $debt->direction === 'payable' ? 'out' : 'in';

        // Create safe transaction
        $snapshot = $this->balanceService->getBalanceSnapshot();

        SafeTransaction::create([
            'transaction_date' => $payment->payment_date,
            'type' => 'debt_payment',
            'source' => $payment->source,
            'direction' => $direction,
            'amount' => $payment->amount,
            'reference_type' => DebtPayment::class,
            'reference_id' => $payment->id,
            'balance_after_cash' => $snapshot['cash'],
            'balance_after_digital' => $snapshot['digital'],
        ]);

        // Update installment if linked
        if ($payment->installment_id) {
            $this->debtService->updateInstallmentAfterPayment($payment->installment);
        }

        // Recalculate debt status
        $this->debtService->recalculateDebtStatus($debt);
    }
}
