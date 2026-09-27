<?php

namespace App\Filament\Widgets;

use App\Models\Debt;
use App\Models\DebtInstallment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class DebtAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $totalPayable = Debt::payable()->whereIn('status', ['active', 'overdue'])->sum('remaining_amount');
        $totalReceivable = Debt::receivable()->whereIn('status', ['active', 'overdue'])->sum('remaining_amount');

        $upcomingIn7Days = DebtInstallment::where('status', 'pending')
            ->whereBetween('due_date', [Carbon::today(), Carbon::today()->addDays(7)])
            ->count();

        $overdueCount = DebtInstallment::where('status', 'overdue')->count();

        return [
            Stat::make('مديونيات علينا', number_format($totalPayable, 2) . ' ج.م')
                ->description('إجمالي المتبقي')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->color('danger')
                ->icon('heroicon-o-arrow-up-circle'),

            Stat::make('مديونيات لنا', number_format($totalReceivable, 2) . ' ج.م')
                ->description('إجمالي المتبقي')
                ->descriptionIcon('heroicon-m-arrow-down-right')
                ->color('success')
                ->icon('heroicon-o-arrow-down-circle'),

            Stat::make('أقساط قادمة (7 أيام)', $upcomingIn7Days)
                ->description($upcomingIn7Days > 0 ? 'يجب المتابعة' : 'لا توجد أقساط قريبة')
                ->descriptionIcon('heroicon-m-clock')
                ->color($upcomingIn7Days > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('أقساط متأخرة', $overdueCount)
                ->description($overdueCount > 0 ? 'تحتاج اهتمام فوري!' : 'لا توجد أقساط متأخرة')
                ->descriptionIcon($overdueCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueCount > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}
