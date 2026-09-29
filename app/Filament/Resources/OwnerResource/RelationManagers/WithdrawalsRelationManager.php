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

                Forms\Components\Toggle::make('is_split')
                    ->label('تقسيم المبلغ (Split Payment)')
                    ->live()
                    ->afterStateHydrated(function (Forms\Components\Toggle $component, ?\App\Models\OwnerWithdrawal $record) {
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
                    ->afterStateHydrated(function ($component, ?\App\Models\OwnerWithdrawal $record) {
                        if (!$record) return;
                        $component->state($record->total_amount);
                    })
                    ->rule(function (Forms\Get $get, ?\App\Models\OwnerWithdrawal $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            if ($get('is_split')) return;
                            $amount = floatval($value);
                            if ($amount <= 0) return;
                            
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
                    ->afterStateHydrated(function ($component, ?\App\Models\OwnerWithdrawal $record) {
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
                    ->rule(function (Forms\Get $get, ?\App\Models\OwnerWithdrawal $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $amount = floatval($value);
                            if ($amount <= 0) return;
                            
                            $service = app(\App\Services\SafeBalanceService::class);
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
                    ->rule(function (Forms\Get $get, ?\App\Models\OwnerWithdrawal $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $amount = floatval($value);
                            if ($amount <= 0) return;
                            
                            $service = app(\App\Services\SafeBalanceService::class);
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
                    ->rule(function (Forms\Get $get, ?\App\Models\OwnerWithdrawal $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $amount = floatval($value);
                            if ($amount <= 0) return;
                            
                            $service = app(\App\Services\SafeBalanceService::class);
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
                    ->rule(function (Forms\Get $get, ?\App\Models\OwnerWithdrawal $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $amount = floatval($value);
                            if ($amount <= 0) return;
                            
                            $service = app(\App\Services\SafeBalanceService::class);
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
                    ->limit(30),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('إضافة مسحوبات')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!isset($data['is_split']) || !$data['is_split']) {
                            $source = $data['single_source'] ?? 'cash';
                            $amount = $data['single_amount'] ?? 0;
                            $data['cash_amount'] = 0; $data['instapay_amount'] = 0;
                            $data['wallet_amount'] = 0; $data['fawry_amount'] = 0;
                            if ($source === 'cash') $data['cash_amount'] = $amount;
                            elseif ($source === 'instapay') $data['instapay_amount'] = $amount;
                            elseif ($source === 'wallet') $data['wallet_amount'] = $amount;
                            elseif ($source === 'fawry') $data['fawry_amount'] = $amount;
                        }
                        unset($data['is_split'], $data['single_amount'], $data['single_source']);
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!isset($data['is_split']) || !$data['is_split']) {
                            $source = $data['single_source'] ?? 'cash';
                            $amount = $data['single_amount'] ?? 0;
                            $data['cash_amount'] = 0; $data['instapay_amount'] = 0;
                            $data['wallet_amount'] = 0; $data['fawry_amount'] = 0;
                            if ($source === 'cash') $data['cash_amount'] = $amount;
                            elseif ($source === 'instapay') $data['instapay_amount'] = $amount;
                            elseif ($source === 'wallet') $data['wallet_amount'] = $amount;
                            elseif ($source === 'fawry') $data['fawry_amount'] = $amount;
                        }
                        unset($data['is_split'], $data['single_amount'], $data['single_source']);
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
