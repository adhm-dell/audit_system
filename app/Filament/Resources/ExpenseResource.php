<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\Employee;
use App\Services\SafeBalanceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'المصاريف';
    protected static ?string $navigationLabel = 'المصاريف';
    protected static ?string $modelLabel = 'مصروف';
    protected static ?string $pluralModelLabel = 'المصاريف';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المصروف')
                    ->icon('heroicon-o-receipt-percent')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('category')
                            ->label('التصنيف')
                            ->options(Expense::categoryLabels())
                            ->required()
                            ->searchable()
                            ->live(),

                        Forms\Components\Toggle::make('is_split')
                            ->label('تقسيم المبلغ (Split Payment)')
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, ?Expense $record) {
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
                            ->afterStateHydrated(function ($component, ?Expense $record) {
                                if (!$record) return;
                                $component->state($record->total_amount);
                            })
                            ->rule(function (Forms\Get $get, ?Expense $record) {
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
                            ->afterStateHydrated(function ($component, ?Expense $record) {
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
                            ->rule(function (Forms\Get $get, ?Expense $record) {
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
                            ->rule(function (Forms\Get $get, ?Expense $record) {
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
                            ->rule(function (Forms\Get $get, ?Expense $record) {
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
                            ->rule(function (Forms\Get $get, ?Expense $record) {
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

                        Forms\Components\Select::make('employee_id')
                            ->label('الموظف')
                            ->options(Employee::pluck('name', 'id'))
                            ->searchable()
                            ->required(fn(Forms\Get $get) => in_array($get('category'), ['employee_advance', 'salary']))
                            ->visible(fn(Forms\Get $get) => in_array($get('category'), ['employee_advance', 'salary'])),

                        Forms\Components\DatePicker::make('expense_date')
                            ->label('تاريخ المصروف')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Forms\Components\TextInput::make('paid_to')
                            ->label('مدفوع لـ (مثل: اسم العامل)')
                            ->visible(fn(Forms\Get $get) => !in_array($get('category'), ['employee_advance', 'salary']))
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('مرفق (فاتورة/إيصال)')
                            ->directory('capital-expenses')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
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
            ->defaultSort('expense_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => Expense::categoryLabels()[$state] ?? $state)
                    ->color(fn(string $state) => match ($state) {
                        'construction' => 'info',
                        'equipment' => 'warning',
                        'maintenance' => 'danger',
                        'renovation' => 'success',
                        'employee_advance' => 'primary',
                        'salary' => 'success',
                        'worker_wages' => 'warning',
                        default => 'gray',
                    }),

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

                Tables\Columns\TextColumn::make('paid_to')
                    ->label('مدفوع لـ')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options(Expense::categoryLabels()),



                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $d) => $q->where('expense_date', '>=', $d))
                            ->when($data['until'], fn($q, $d) => $q->where('expense_date', '<=', $d));
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
