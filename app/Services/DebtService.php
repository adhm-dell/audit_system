<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\DebtInstallment;
use Carbon\Carbon;

class DebtService
{
    /**
     * Auto-generate monthly installments for a scheduled debt.
     */
    public function generateInstallments(Debt $debt, int $count): void
    {
        $installmentAmount = round($debt->total_amount / $count, 2);
        $remainder = $debt->total_amount - ($installmentAmount * $count);

        $startDate = $debt->start_date->copy();

        for ($i = 1; $i <= $count; $i++) {
            $amount = $installmentAmount;

            // Add any rounding remainder to the last installment
            if ($i === $count && $remainder != 0) {
                $amount += $remainder;
            }

            DebtInstallment::create([
                'debt_id' => $debt->id,
                'installment_number' => $i,
                'due_date' => $startDate->copy()->addMonths($i - 1),
                'amount' => $amount,
                'status' => 'pending',
                'paid_amount' => 0,
            ]);
        }

        // Update due_date on the debt to the last installment date
        $debt->update([
            'due_date' => $startDate->copy()->addMonths($count - 1),
        ]);
    }

    /**
     * Recalculate a debt's remaining_amount and status based on its payments.
     */
    public function recalculateDebtStatus(Debt $debt): void
    {
        $totalPaid = $debt->payments()->sum('amount');
        $remaining = max(0, $debt->total_amount - $totalPaid);

        $status = $debt->status;

        if ($remaining <= 0) {
            $status = 'paid';
        } elseif ($debt->status !== 'cancelled') {
            // Check if overdue
            if ($debt->due_date && $debt->due_date->isPast()) {
                $status = 'overdue';
            } else {
                $status = 'active';
            }
        }

        $debt->update([
            'remaining_amount' => $remaining,
            'status' => $status,
        ]);
    }

    /**
     * Update an installment's status and paid_amount after a payment.
     */
    public function updateInstallmentAfterPayment(DebtInstallment $installment): void
    {
        $totalPaid = $installment->payments()->sum('amount');
        $installment->paid_amount = $totalPaid;

        if ($totalPaid >= $installment->amount) {
            $installment->status = 'paid';
        } elseif ($totalPaid > 0) {
            $installment->status = 'partial';
        }

        $installment->save();
    }
}
