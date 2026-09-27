<?php

namespace App\Filament\Resources\OwnerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawalsRelationManager extends RelationManager
{
    protected static string $relationship = 'withdrawals';
    protected static ?string $title = 'المسحوبات';
    protected static ?string $modelLabel = 'مسحوبات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('withdrawal_date')
                    ->label('تاريخ السحب')
                    ->required()
                    ->default(now())
                    ->native(false),

                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('ج.م'),

                Forms\Components\Select::make('source')
                    ->label('المصدر')
                    ->options([
                        'cash' => 'نقدي',
                        'digital' => 'شبكة/رقمي',
                    ])
                    ->required()
                    ->default('cash'),

                Forms\Components\TextInput::make('reason')
                    ->label('السبب')
                    ->maxLength(255),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('withdrawal_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('withdrawal_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('EGP')->label('الإجمالي')),

                Tables\Columns\TextColumn::make('source')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'cash' ? 'نقدي' : 'شبكة')
                    ->color(fn(string $state) => $state === 'cash' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(30),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة مسحوبات'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
