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
        $instapay = $service->getInstapayBalance();
        $wallet = $service->getWalletBalance();
        $fawry = $service->getFawryBalance();
        $total = $cash + $instapay + $wallet + $fawry;

        return [
            Stat::make('رصيد الكاش', number_format($cash, 2) . ' ج.م')
                ->color($cash >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-banknotes'),

            Stat::make('رصيد إنستاباي', number_format($instapay, 2) . ' ج.م')
                ->color($instapay >= 0 ? 'info' : 'danger')
                ->icon('heroicon-o-arrows-right-left'),

            Stat::make('رصيد المحفظة', number_format($wallet, 2) . ' ج.م')
                ->color($wallet >= 0 ? 'warning' : 'danger')
                ->icon('heroicon-o-device-phone-mobile'),

            Stat::make('رصيد فوري', number_format($fawry, 2) . ' ج.م')
                ->color($fawry >= 0 ? 'primary' : 'danger')
                ->icon('heroicon-o-credit-card'),

            Stat::make('الإجمالي', number_format($total, 2) . ' ج.م')
                ->description('الكاش + جميع القنوات الرقمية')
                ->color($total >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-building-library'),
        ];
    }
}
