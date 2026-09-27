<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerDepositResource\Pages;
use App\Models\Owner;
use App\Models\OwnerDeposit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OwnerDepositResource extends Resource
{
    protected static ?string $model = OwnerDeposit::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'الشركاء';
    protected static ?string $navigationLabel = 'إيداعات وتمويل الخزنة';
    protected static ?string $modelLabel = 'إيداع / رصيد ابتدائي';
    protected static ?string $pluralModelLabel = 'الإيداعات والرصيد الابتدائي';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الإيداع / الرصيد الابتدائي')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('owner_id')
                            ->label('الشريك (اختياري في حالة رصيد ابتدائي عام)')
                            ->options(Owner::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('deposit_date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cash_amount')
                            ->label('المبلغ النقدي')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م'),

                        Forms\Components\TextInput::make('instapay_amount')
                            ->label('مبلغ إنستاباي')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م'),

                        Forms\Components\TextInput::make('wallet_amount')
                            ->label('مبلغ المحفظة الإلكترونية')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م'),

                        Forms\Components\TextInput::make('fawry_amount')
                            ->label('مبلغ فوري')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م'),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات (مثال: رصيد افتتاحي)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('deposit_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('الشريك')
                    ->default('رصيد ابتدائي')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('deposit_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(['cash_amount', 'instapay_amount', 'wallet_amount', 'fawry_amount'])
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('cash_amount')
                    ->label('نقدي')
                    ->money('EGP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('instapay_amount')
                    ->label('إنستاباي')
                    ->money('EGP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('wallet_amount')
                    ->label('محفظة')
                    ->money('EGP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fawry_amount')
                    ->label('فوري')
                    ->money('EGP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('owner_id')
                    ->label('الشريك')
                    ->options(Owner::pluck('name', 'id'))
                    ->searchable(),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOwnerDeposits::route('/'),
        ];
    }
}
