<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarEnvelopeResource\Pages;
use App\Models\BarEnvelope;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BarEnvelopeResource extends Resource
{
    protected static ?string $model = BarEnvelope::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'العمليات اليومية';
    protected static ?string $navigationLabel = 'ظروف البار';
    protected static ?string $modelLabel = 'ظرف بار';
    protected static ?string $pluralModelLabel = 'ظروف البار';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الظرف')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('bar_date')
                            ->label('تاريخ البار')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(now())
                            ->native(false)
                            ->displayFormat('Y-m-d'),
                    ]),

                Forms\Components\Section::make('المبالغ المحصلة')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cash_total')
                            ->label('إجمالي الكاش')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('network_instapay_total')
                            ->label('إجمالي إنستاباي')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('network_wallet_total')
                            ->label('إجمالي المحافظ')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('network_fawry_total')
                            ->label('إجمالي فوري')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('network_total')
                            ->label('إجمالي الشبكة')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(fn (Forms\Get $get) => floatval($get('network_instapay_total')) + floatval($get('network_wallet_total')) + floatval($get('network_fawry_total')))
                            ->prefix('ج.م'),
                    ]),

                Forms\Components\Section::make('المديونيات')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\TextInput::make('debts_total')
                            ->label('مبيعات آجلة (لم تُحصل)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('ج.م')
                            ->helperText('إذا كان هناك مبيعات على الحساب خلال اليوم ولم تسدد، سيتم إنشاء مديونية بهذا المبلغ باسم "عملاء البار"'),
                    ]),

                Forms\Components\Section::make('ملاحظات')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('bar_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('bar_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('cash_total')
                    ->label('إجمالي الكاش')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('network_total')
                    ->label('إجمالي الشبكة')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('debts_total')
                    ->label('المديونيات')
                    ->money('EGP')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->where('bar_date', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->where('bar_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListBarEnvelopes::route('/'),
            'create' => Pages\CreateBarEnvelope::route('/create'),
            'edit' => Pages\EditBarEnvelope::route('/{record}/edit'),
        ];
    }
}
