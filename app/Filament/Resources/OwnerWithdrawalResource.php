<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerWithdrawalResource\Pages;
use App\Models\Owner;
use App\Models\OwnerWithdrawal;
use App\Services\SafeBalanceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OwnerWithdrawalResource extends Resource
{
    protected static ?string $model = OwnerWithdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup = 'الشركاء';
    protected static ?string $navigationLabel = 'المسحوبات';
    protected static ?string $modelLabel = 'مسحوبات';
    protected static ?string $pluralModelLabel = 'المسحوبات';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المسحوبات')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('owner_id')
                            ->label('الشريك')
                            ->options(Owner::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),

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
                            ->prefix('ج.م')
                            ->live(onBlur: true),

                        Forms\Components\Select::make('source')
                            ->label('المصدر')
                            ->options([
                                'cash' => 'نقدي',
                                'digital' => 'شبكة/رقمي',
                            ])
                            ->required()
                            ->default('cash')
                            ->live(),

                        Forms\Components\Select::make('digital_channel')
                            ->label('قناة الدفع الرقمي')
                            ->options([
                                'instapay' => 'إنستاباي',
                                'wallet' => 'محفظة إلكترونية',
                                'fawry' => 'فوري',
                            ])
                            ->visible(fn(Forms\Get $get) => $get('source') === 'digital'),

                        Forms\Components\Placeholder::make('balance_warning')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $source = $get('source') ?? 'cash';
                                $channel = $get('digital_channel');
                                $amount = floatval($get('amount') ?? 0);
                                if ($amount <= 0) {
                                    return '';
                                }
                                $service = app(SafeBalanceService::class);
                                $balance = $service->getBalance($source, $channel);
                                
                                $sourceLabel = $source === 'cash' ? 'الكاش' : 'الشبكة';
                                if ($source === 'digital' && $channel) {
                                    $channelNames = ['instapay' => 'إنستاباي', 'wallet' => 'محفظة', 'fawry' => 'فوري'];
                                    $sourceLabel .= ' (' . ($channelNames[$channel] ?? $channel) . ')';
                                }

                                if ($balance < $amount) {
                                    return "⚠️ تنبيه: رصيد {$sourceLabel} الحالي " . number_format($balance, 2) . " ج.م — المبلغ المطلوب يتجاوز الرصيد!";
                                }
                                return "✅ رصيد {$sourceLabel} الحالي: " . number_format($balance, 2) . " ج.م";
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('reason')
                            ->label('السبب')
                            ->maxLength(255)
                            ->columnSpanFull(),

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
            ->defaultSort('withdrawal_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('الشريك')
                    ->sortable()
                    ->searchable(),

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
                    ->formatStateUsing(function (string $state, $record) {
                        if ($state === 'cash') return 'نقدي';
                        $channelNames = ['instapay' => 'إنستاباي', 'wallet' => 'محفظة', 'fawry' => 'فوري'];
                        $channel = $record->digital_channel;
                        return 'شبكة' . ($channel ? ' (' . ($channelNames[$channel] ?? $channel) . ')' : '');
                    })
                    ->color(fn(string $state) => $state === 'cash' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('owner_id')
                    ->label('الشريك')
                    ->options(Owner::pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('source')
                    ->label('المصدر')
                    ->options([
                        'cash' => 'نقدي',
                        'digital' => 'شبكة',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $d) => $q->where('withdrawal_date', '>=', $d))
                            ->when($data['until'], fn($q, $d) => $q->where('withdrawal_date', '<=', $d));
                    }),
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
            'index' => Pages\ListOwnerWithdrawals::route('/'),
            'create' => Pages\CreateOwnerWithdrawal::route('/create'),
            'edit' => Pages\EditOwnerWithdrawal::route('/{record}/edit'),
        ];
    }
}
