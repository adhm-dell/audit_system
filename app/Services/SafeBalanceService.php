<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\DailyEnvelope;
use App\Models\BarEnvelope;
use App\Models\DebtPayment;
use App\Models\OwnerWithdrawal;
use App\Models\OwnerDeposit;
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

        $ownerDeposits = OwnerDeposit::sum('cash_amount');

        $withdrawals = OwnerWithdrawal::where('source', 'cash')->sum('amount');

        $expenses = Expense::where('source', 'cash')->sum('amount');

        $debtPaymentsOut = DebtPayment::where('source', 'cash')
            ->whereHas('debt', fn($q) => $q->where('direction', 'payable'))
            ->sum('amount');

        $debtPaymentsIn = DebtPayment::where('source', 'cash')
            ->whereHas('debt', fn($q) => $q->where('direction', 'receivable'))
            ->sum('amount');

        return $deposits + $barDeposits + $ownerDeposits - $withdrawals - $expenses - $debtPaymentsOut + $debtPaymentsIn;
    }

    /**
     * Get digital balance for a specific channel.
     */
    public function getDigitalChannelBalance(string $channel): float
    {
        $deposits = DailyEnvelope::sum("network_{$channel}_total");
        $barDeposits = BarEnvelope::sum("network_{$channel}_total");

        $ownerDeposits = OwnerDeposit::sum("{$channel}_amount");

        $envelopeExpenses = DailyEnvelope::where('expenses_paid_from', 'digital')
            ->where('digital_channel', $channel)
            ->sum('expenses_total');

        $withdrawals = OwnerWithdrawal::where('source', 'digital')
            ->where('digital_channel', $channel)
            ->sum('amount');

        $expenses = Expense::where('source', 'digital')
            ->where('digital_channel', $channel)
            ->sum('amount');

        $debtPaymentsOut = DebtPayment::where('source', 'digital')
            ->where('digital_channel', $channel)
            ->whereHas('debt', fn($q) => $q->where('direction', 'payable'))
            ->sum('amount');

        $debtPaymentsIn = DebtPayment::where('source', 'digital')
            ->where('digital_channel', $channel)
            ->whereHas('debt', fn($q) => $q->where('direction', 'receivable'))
            ->sum('amount');

        return $deposits + $barDeposits + $ownerDeposits - $envelopeExpenses - $withdrawals - $expenses - $debtPaymentsOut + $debtPaymentsIn;
    }

    public function getInstapayBalance(): float { return $this->getDigitalChannelBalance('instapay'); }
    public function getWalletBalance(): float { return $this->getDigitalChannelBalance('wallet'); }
    public function getFawryBalance(): float { return $this->getDigitalChannelBalance('fawry'); }

    /**
     * Get current total digital/network balance.
     */
    public function getDigitalBalance(): float
    {
        return $this->getInstapayBalance() + $this->getWalletBalance() + $this->getFawryBalance();
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

        if ($source === 'cash') {
            $ownerDeposits = OwnerDeposit::where('deposit_date', '<=', $date)->sum('cash_amount');
        } else {
            $ownerDeposits = OwnerDeposit::where('deposit_date', '<=', $date)
                ->sum(DB::raw('instapay_amount + wallet_amount + fawry_amount'));
        }

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

        return $deposits + $barDeposits + $ownerDeposits - $withdrawals - $expenses - $debtPaymentsOut + $debtPaymentsIn;
    }

    /**
     * Check if a withdrawal/expense amount would push the balance negative.
     */
    public function wouldGoNegative(string $source, float $amount, ?string $channel = null): bool
    {
        $currentBalance = $this->getBalance($source, $channel);
        return ($currentBalance - $amount) < 0;
    }

    /**
     * Get the current balance for a specific source and optional channel.
     */
    public function getBalance(string $source, ?string $channel = null): float
    {
        if ($source === 'cash') {
            return $this->getCashBalance();
        }

        if ($channel) {
            return $this->getDigitalChannelBalance($channel);
        }

        return $this->getDigitalBalance();
    }
}
