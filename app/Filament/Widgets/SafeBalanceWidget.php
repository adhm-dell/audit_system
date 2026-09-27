<?php

namespace App\Filament\Widgets;

use App\Services\SafeBalanceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SafeBalanceWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $service = app(SafeBalanceService::class);
        $cash = $service->getCashBalance();
        $digital = $service->getDigitalBalance();

        return [
            Stat::make('رصيد الكاش', number_format($cash, 2) . ' ج.م')
                ->description($cash >= 0 ? 'رصيد إيجابي' : 'رصيد سالب — تنبيه!')
                ->descriptionIcon($cash >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($cash >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-banknotes'),

            Stat::make('رصيد الشبكة', number_format($digital, 2) . ' ج.م')
                ->description($digital >= 0 ? 'رصيد إيجابي' : 'رصيد سالب — تنبيه!')
                ->descriptionIcon($digital >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($digital >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-signal'),

            Stat::make('إجمالي الخزنة', number_format($cash + $digital, 2) . ' ج.م')
                ->description('كاش + شبكة')
                ->descriptionIcon('heroicon-m-calculator')
                ->color(($cash + $digital) >= 0 ? 'info' : 'danger')
                ->icon('heroicon-o-building-library'),
        ];
    }
}
