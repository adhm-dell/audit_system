<?php

namespace App\Filament\Pages;

use App\Models\SafeTransaction;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SafeLedger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'الخزنة والتقارير';
    protected static ?string $navigationLabel = 'سجل الخزنة';
    protected static ?string $title = 'سجل الخزنة';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.safe-ledger';

    public function table(Table $table): Table
    {
        return $table
            ->query(SafeTransaction::query())
            ->defaultSort('transaction_date', 'desc')
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
                        'bar_deposit' => 'success',
                        'owner_withdrawal' => 'warning',
                        'expense' => 'danger',
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options(SafeTransaction::typeLabels()),

                Tables\Filters\SelectFilter::make('source')
                    ->label('المصدر')
                    ->options([
                        'cash' => 'نقدي',
                        'digital' => 'شبكة',
                    ]),

                Tables\Filters\SelectFilter::make('direction')
                    ->label('الاتجاه')
                    ->options([
                        'in' => 'وارد',
                        'out' => 'صادر',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $d) => $q->where('transaction_date', '>=', $d))
                            ->when($data['until'], fn($q, $d) => $q->where('transaction_date', '<=', $d));
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
