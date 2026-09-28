<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DailyEnvelopeItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'dailyEnvelopeItems';
    protected static ?string $title = 'سجل السلف من اليوميات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('category')
                    ->label('التصنيف')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('category')
            ->columns([
                Tables\Columns\TextColumn::make('dailyEnvelope.envelope_date')
                    ->label('تاريخ اليومية')
                    ->date('Y-m-d')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->formatStateUsing(fn (string $state) => \App\Models\DailyEnvelopeItem::categoryLabels()[$state] ?? $state),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP'),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(30),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
