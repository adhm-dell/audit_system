<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\DailyEnvelope;
use App\Models\BarEnvelope;
use App\Models\DebtPayment;
use App\Models\OwnerWithdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SafeBalanceService
{
    /**
     * Get current cash balance.
     */
    public function getCashBalance(): float
    {
        $deposits = DailyEnvelope::sum('net_cash_to_safe');
        $barDeposits = BarEnvelope::sum('cash_total');

        $withdrawals = OwnerWithdrawal::where('source', 'cash')->sum('amount');

        $expenses = Expense::where('source', 'cash')->sum('amount');

        $debtPaymentsOut = DebtPayment::where('source', 'cash')
            ->whereHas('debt', fn($q) => $q->where('direction', 'payable'))
            ->sum('amount');

        $debtPaymentsIn = DebtPayment::where('source', 'cash')
            ->whereHas('debt', fn($q) => $q->where('direction', 'receivable'))
            ->sum('amount');

        return $deposits + $barDeposits - $withdrawals - $expenses - $debtPaymentsOut + $debtPaymentsIn;
    }

    /**
     * Get current digital/network balance.
     */
    public function getDigitalBalance(): float
    {
        $deposits = DailyEnvelope::sum('net_digital');
        $barDeposits = BarEnvelope::sum('network_total');

        $withdrawals = OwnerWithdrawal::where('source', 'digital')->sum('amount');

        $expenses = Expense::where('source', 'digital')->sum('amount');

        $debtPaymentsOut = DebtPayment::where('source', 'digital')
            ->whereHas('debt', fn($q) => $q->where('direction', 'payable'))
            ->sum('amount');

        $debtPaymentsIn = DebtPayment::where('source', 'digital')
            ->whereHas('debt', fn($q) => $q->where('direction', 'receivable'))
            ->sum('amount');

        return $deposits + $barDeposits - $withdrawals - $expenses - $debtPaymentsOut + $debtPaymentsIn;
    }

    /**
     * Get both balances as a snapshot array.
     */
    public function getBalanceSnapshot(): array
    {
        return [
            'cash' => $this->getCashBalance(),
            'digital' => $this->getDigitalBalance(),
        ];
    }

    /**
     * Get balance as of a specific date for a given source.
     */
    public function getBalanceAsOf(Carbon $date, string $source = 'cash'): float
    {
        $column = $source === 'cash' ? 'net_cash_to_safe' : 'net_digital';

        $deposits = DailyEnvelope::where('envelope_date', '<=', $date)->sum($column);
        $barDeposits = BarEnvelope::where('bar_date', '<=', $date)->sum($source === 'cash' ? 'cash_total' : 'network_total');

        $withdrawals = OwnerWithdrawal::where('source', $source)
            ->where('withdrawal_date', '<=', $date)
            ->sum('amount');

        $expenses = Expense::where('source', $source)
            ->where('expense_date', '<=', $date)
            ->sum('amount');

        $debtPaymentsOut = DebtPayment::where('source', $source)
            ->where('payment_date', '<=', $date)
            ->whereHas('debt', fn($q) => $q->where('direction', 'payable'))
            ->sum('amount');

        $debtPaymentsIn = DebtPayment::where('source', $source)
            ->where('payment_date', '<=', $date)
            ->whereHas('debt', fn($q) => $q->where('direction', 'receivable'))
            ->sum('amount');

        return $deposits + $barDeposits - $withdrawals - $expenses - $debtPaymentsOut + $debtPaymentsIn;
    }

    /**
     * Check if a withdrawal/expense amount would push the balance negative.
     */
    public function wouldGoNegative(string $source, float $amount): bool
    {
        $currentBalance = $source === 'cash'
            ? $this->getCashBalance()
            : $this->getDigitalBalance();

        return ($currentBalance - $amount) < 0;
    }

    /**
     * Get the current balance for a specific source.
     */
    public function getBalance(string $source): float
    {
        return $source === 'cash'
            ? $this->getCashBalance()
            : $this->getDigitalBalance();
    }
}
