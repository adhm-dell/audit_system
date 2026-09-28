<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebtPaymentResource\Pages;
use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\DebtPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DebtPaymentResource extends Resource
{
    protected static ?string $model = DebtPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'المديونيات';
    protected static ?string $navigationLabel = 'المدفوعات';
    protected static ?string $modelLabel = 'دفعة';
    protected static ?string $pluralModelLabel = 'المدفوعات';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات الدفعة')
                    ->icon('heroicon-o-credit-card')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('debt_id')
                            ->label('المديونية')
                            ->options(
                                Debt::whereIn('status', ['active', 'overdue'])
                                    ->get()
                                    ->mapWithKeys(fn($d) => [
                                        $d->id => "{$d->title} — متبقي: " . number_format($d->remaining_amount, 2) . " ج.م",
                                    ])
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\Select::make('installment_id')
                            ->label('القسط (اختياري)')
                            ->options(function (Forms\Get $get) {
                                $debtId = $get('debt_id');
                                if (!$debtId) return [];
                                return DebtInstallment::where('debt_id', $debtId)
                                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                                    ->get()
                                    ->mapWithKeys(fn($i) => [
                                        $i->id => "قسط #{$i->installment_number} — متبقي: " . number_format($i->amount - $i->paid_amount, 2) . " ج.م",
                                    ]);
                            })
                            ->searchable()
                            ->preload(),

                        Forms\Components\Toggle::make('is_split')
                            ->label('تقسيم المبلغ (Split Payment)')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, ?DebtPayment $record) {
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
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, ?DebtPayment $record) {
                                if (!$record) return;
                                $component->state($record->total_amount);
                            })
                            ->rule(function (Forms\Get $get, ?DebtPayment $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    if ($get('is_split')) return;
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $debt_id = $get('debt_id');
                                    if ($debt_id) {
                                        $debt = \App\Models\Debt::find($debt_id);
                                        if ($debt && $debt->direction === 'receivable') {
                                            return; // Incoming payment, no balance check
                                        }
                                    }
                                    
                                    $source = $get('single_source');
                                    $service = app(\App\Services\SafeBalanceService::class);
                                    
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
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, ?DebtPayment $record) {
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
                            ->rule(function (Forms\Get $get, ?DebtPayment $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $debtId = $get('debt_id');
                                    if (!$debtId) return;
                                    $debt = \App\Models\Debt::find($debtId);
                                    if ($debt && $debt->direction === 'payable') {
                                        $service = app(\App\Services\SafeBalanceService::class);
                                        $balance = $service->getBalance('cash');
                                        if ($record) $balance += floatval($record->cash_amount);
                                        if ($amount > $balance) {
                                            $fail('الرصيد النقدي غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                        }
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
                            ->rule(function (Forms\Get $get, ?DebtPayment $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $debtId = $get('debt_id');
                                    if (!$debtId) return;
                                    $debt = \App\Models\Debt::find($debtId);
                                    if ($debt && $debt->direction === 'payable') {
                                        $service = app(\App\Services\SafeBalanceService::class);
                                        $balance = $service->getBalance('digital', 'instapay');
                                        if ($record) $balance += floatval($record->instapay_amount);
                                        if ($amount > $balance) {
                                            $fail('رصيد إنستاباي غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                        }
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
                            ->rule(function (Forms\Get $get, ?DebtPayment $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $debtId = $get('debt_id');
                                    if (!$debtId) return;
                                    $debt = \App\Models\Debt::find($debtId);
                                    if ($debt && $debt->direction === 'payable') {
                                        $service = app(\App\Services\SafeBalanceService::class);
                                        $balance = $service->getBalance('digital', 'wallet');
                                        if ($record) $balance += floatval($record->wallet_amount);
                                        if ($amount > $balance) {
                                            $fail('رصيد المحفظة غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                        }
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
                            ->rule(function (Forms\Get $get, ?DebtPayment $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $amount = floatval($value);
                                    if ($amount <= 0) return;
                                    
                                    $debtId = $get('debt_id');
                                    if (!$debtId) return;
                                    $debt = \App\Models\Debt::find($debtId);
                                    if ($debt && $debt->direction === 'payable') {
                                        $service = app(\App\Services\SafeBalanceService::class);
                                        $balance = $service->getBalance('digital', 'fawry');
                                        if ($record) $balance += floatval($record->fawry_amount);
                                        if ($amount > $balance) {
                                            $fail('رصيد فوري غير كافٍ. المتاح: ' . number_format($balance, 2) . ' ج.م');
                                        }
                                    }
                                };
                            }),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ الدفع')
                            ->required()
                            ->default(now())
                            ->native(false),

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
            ->defaultSort('payment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('debt.title')
                    ->label('المديونية')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('debt.direction')
                    ->label('الاتجاه')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'payable' ? 'علينا' : 'لنا')
                    ->color(fn(string $state) => $state === 'payable' ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('payment_date')
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

                Tables\Columns\TextColumn::make('installment.installment_number')
                    ->label('رقم القسط')
                    ->default('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('debt_id')
                    ->label('المديونية')
                    ->options(Debt::pluck('title', 'id'))
                    ->searchable(),



                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $d) => $q->where('payment_date', '>=', $d))
                            ->when($data['until'], fn($q, $d) => $q->where('payment_date', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDebtPayments::route('/'),
            'create' => Pages\CreateDebtPayment::route('/create'),
        ];
    }
}
