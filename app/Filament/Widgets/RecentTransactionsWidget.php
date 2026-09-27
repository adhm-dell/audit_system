<?php

namespace App\Filament\Widgets;

use App\Models\SafeTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'آخر حركات الخزنة';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SafeTransaction::query()->latest('transaction_date')->latest('id')
            )
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => SafeTransaction::typeLabels()[$state] ?? $state)
                    ->color(fn(string $state) => match ($state) {
                        'daily_deposit' => 'success',
                        'owner_withdrawal' => 'warning',
                        'capital_expense' => 'danger',
                        'debt_payment' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('source')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'cash' ? 'نقدي' : 'شبكة')
                    ->color(fn(string $state) => $state === 'cash' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('direction')
                    ->label('الاتجاه')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'in' ? '↗️ وارد' : '↘️ صادر')
                    ->color(fn(string $state) => $state === 'in' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_after_cash')
                    ->label('رصيد الكاش بعد')
                    ->money('EGP'),

                Tables\Columns\TextColumn::make('balance_after_digital')
                    ->label('رصيد الشبكة بعد')
                    ->money('EGP'),
            ]);
    }
}
