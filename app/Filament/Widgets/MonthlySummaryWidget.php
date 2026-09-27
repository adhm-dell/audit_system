<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\OwnerWithdrawal;
use App\Models\DailyEnvelope;
use App\Models\BarEnvelope;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class MonthlySummaryWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalWithdrawals = OwnerWithdrawal::whereBetween('withdrawal_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $totalExpenses = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $gymDeposits = DailyEnvelope::whereBetween('envelope_date', [$startOfMonth, $endOfMonth])
            ->sum('cash_total') + DailyEnvelope::whereBetween('envelope_date', [$startOfMonth, $endOfMonth])->sum('network_total');

        $barDeposits = BarEnvelope::whereBetween('bar_date', [$startOfMonth, $endOfMonth])
            ->sum('cash_total') + BarEnvelope::whereBetween('bar_date', [$startOfMonth, $endOfMonth])->sum('network_total');

        return [
            Stat::make('مسحوبات الشهر', number_format($totalWithdrawals, 2) . ' ج.م')
                ->description('إجمالي مسحوبات الشركاء')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning')
                ->icon('heroicon-o-arrow-up-tray'),

            Stat::make('مصاريف الشهر', number_format($totalExpenses, 2) . ' ج.م')
                ->description('إجمالي المصاريف')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('danger')
                ->icon('heroicon-o-receipt-percent'),

            Stat::make('إيداعات الجيم', number_format($gymDeposits, 2) . ' ج.م')
                ->description('كاش + شبكة')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray'),

            Stat::make('إيداعات البار', number_format($barDeposits, 2) . ' ج.م')
                ->description('كاش + شبكة')
                ->color('success')
                ->icon('heroicon-o-building-storefront'),
        ];
    }
}
