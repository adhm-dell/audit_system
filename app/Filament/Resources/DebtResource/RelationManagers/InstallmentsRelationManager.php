<?php

namespace App\Filament\Resources\DebtResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';
    protected static ?string $title = 'الأقساط';
    protected static ?string $modelLabel = 'قسط';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('installment_number')
            ->columns([
                Tables\Columns\TextColumn::make('installment_number')
                    ->label('رقم القسط')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ القسط')
                    ->money('EGP'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('المدفوع')
                    ->money('EGP'),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->money('EGP')
                    ->getStateUsing(fn($record) => $record->amount - $record->paid_amount)
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pending' => 'بانتظار',
                        'partial' => 'جزئي',
                        'paid' => 'مسدد',
                        'overdue' => 'متأخر',
                        default => $state,
                    })
                    ->color(fn(string $state) => match ($state) {
                        'pending' => 'warning',
                        'partial' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }
}
