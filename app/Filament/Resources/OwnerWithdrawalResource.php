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

                        Forms\Components\Toggle::make('is_split')
                            ->label('تقسيم المبلغ (Split Payment)')
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, ?OwnerWithdrawal $record) {
                                if (!$record) return;
                                $count = 0;
                                if ($record->cash_amount > 0) $count++;
                                if ($record->instapay_amount > 0) $count++;
                                if ($record->wallet_amount > 0) $count++;
                                if ($record->fawry_amount > 0) $count++;
                                $component->state($count > 1);
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('single_amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->prefix('ج.م')
                            ->visible(fn(Forms\Get $get) => !$get('is_split'))
                            ->required(fn(Forms\Get $get) => !$get('is_split'))
                            ->afterStateHydrated(function ($component, ?OwnerWithdrawal $record) {
                                if (!$record) return;
                                $component->state($record->total_amount);
                            })
                            ->rule(function (Forms\Get $get, ?OwnerWithdrawal $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    if ($get('is_split')) return;
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $source = $get('single_source');
                                    $service = app(SafeBalanceService::class);
                                    
                                    if ($source === 'cash') $balance = $service->getBalance('cash');
                                    elseif (in_array($source, ['instapay', 'wallet', 'fawry'])) $balance = $service->getBalance('digital', $source);
                                    else return;

                                    if ($record) {
                                        if ($source === 'cash') $balance += floatval($record->cash_amount);
                                        elseif ($source === 'instapay') $balance += floatval($record->instapay_amount);
                                        elseif ($source === 'wallet') $balance += floatval($record->wallet_amount);
                                        elseif ($source === 'fawry') $balance += floatval($record->fawry_amount);
                                    }

                                    if ($amount > $balance) {
                                        $fail('الرصيد غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                    }
                                };
                            }),

                        Forms\Components\Select::make('single_source')
                            ->label('المصدر')
                            ->options([
                                'cash' => 'نقدي',
                                'instapay' => 'إنستاباي',
                                'wallet' => 'محفظة',
                                'fawry' => 'فوري',
                            ])
                            ->default('cash')
                            ->visible(fn(Forms\Get $get) => !$get('is_split'))
                            ->required(fn(Forms\Get $get) => !$get('is_split'))
                            ->afterStateHydrated(function ($component, ?OwnerWithdrawal $record) {
                                if (!$record) return;
                                if ($record->instapay_amount > 0) $component->state('instapay');
                                elseif ($record->wallet_amount > 0) $component->state('wallet');
                                elseif ($record->fawry_amount > 0) $component->state('fawry');
                                else $component->state('cash');
                            }),

                        Forms\Components\TextInput::make('cash_amount')
                            ->label('المبلغ النقدي')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true)
                            ->visible(fn(Forms\Get $get) => $get('is_split'))
                            ->rule(function (Forms\Get $get, ?OwnerWithdrawal $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $service = app(SafeBalanceService::class);
                                    $balance = $service->getBalance('cash');
                                    
                                    if ($record) {
                                        $balance += floatval($record->cash_amount);
                                    }

                                    if ($amount > $balance) {
                                        $fail('الرصيد النقدي غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                    }
                                };
                            }),

                        Forms\Components\TextInput::make('instapay_amount')
                            ->label('مبلغ إنستاباي')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true)
                            ->visible(fn(Forms\Get $get) => $get('is_split'))
                            ->rule(function (Forms\Get $get, ?OwnerWithdrawal $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $service = app(SafeBalanceService::class);
                                    $balance = $service->getBalance('digital', 'instapay');
                                    
                                    if ($record) {
                                        $balance += floatval($record->instapay_amount);
                                    }

                                    if ($amount > $balance) {
                                        $fail('رصيد إنستاباي غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                    }
                                };
                            }),

                        Forms\Components\TextInput::make('wallet_amount')
                            ->label('مبلغ المحفظة الإلكترونية')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true)
                            ->visible(fn(Forms\Get $get) => $get('is_split'))
                            ->rule(function (Forms\Get $get, ?OwnerWithdrawal $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $service = app(SafeBalanceService::class);
                                    $balance = $service->getBalance('digital', 'wallet');
                                    
                                    if ($record) {
                                        $balance += floatval($record->wallet_amount);
                                    }

                                    if ($amount > $balance) {
                                        $fail('رصيد المحفظة غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                    }
                                };
                            }),

                        Forms\Components\TextInput::make('fawry_amount')
                            ->label('مبلغ فوري')
                            ->numeric()
                            ->default(0)
                            ->prefix('ج.م')
                            ->live(onBlur: true)
                            ->visible(fn(Forms\Get $get) => $get('is_split'))
                            ->rule(function (Forms\Get $get, ?OwnerWithdrawal $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $service = app(SafeBalanceService::class);
                                    $balance = $service->getBalance('digital', 'fawry');
                                    
                                    if ($record) {
                                        $balance += floatval($record->fawry_amount);
                                    }

                                    if ($amount > $balance) {
                                        $fail('رصيد فوري غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                    }
                                };
                            }),



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
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->reorder()->orderBy('withdrawal_date', 'desc')->orderBy('id', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('الشريك')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('withdrawal_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(['cash_amount', 'instapay_amount', 'wallet_amount', 'fawry_amount'])
                    ->badge()
                    ->color('danger'),

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
