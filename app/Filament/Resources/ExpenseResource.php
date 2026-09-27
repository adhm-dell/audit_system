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
                            ->visible(fn(Forms\Get $get) => $get('source') === 'digital')
                            ->required(fn(Forms\Get $get) => $get('source') === 'digital'),

                        Forms\Components\Select::make('employee_id')
                            ->label('الموظف')
                            ->options(Employee::pluck('name', 'id'))
                            ->searchable()
                            ->required(fn(Forms\Get $get) => in_array($get('category'), ['employee_advance', 'salary']))
                            ->visible(fn(Forms\Get $get) => in_array($get('category'), ['employee_advance', 'salary'])),

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

                Tables\Columns\TextColumn::make('paid_to')
                    ->label('مدفوع لـ')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options(Expense::categoryLabels()),

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
