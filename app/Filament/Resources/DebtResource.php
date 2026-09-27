<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebtResource\Pages;
use App\Filament\Resources\DebtResource\RelationManagers;
use App\Models\Debt;
use App\Services\DebtService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DebtResource extends Resource
{
    protected static ?string $model = Debt::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'المديونيات';
    protected static ?string $navigationLabel = 'المديونيات';
    protected static ?string $modelLabel = 'مديونية';
    protected static ?string $pluralModelLabel = 'المديونيات';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('بيانات المديونية')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('direction')
                                    ->label('الاتجاه')
                                    ->options([
                                        'payable' => 'علينا (مديونية علينا)',
                                        'receivable' => 'لنا (مديونية لنا)',
                                    ])
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('type')
                                    ->label('النوع')
                                    ->options([
                                        'loan' => 'قرض',
                                        'installment_debt' => 'دين بأقساط',
                                        'irregular_debt' => 'دين غير منتظم',
                                    ])
                                    ->required()
                                    ->live(),

                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان المديونية')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('creditor_or_debtor_name')
                                    ->label(fn(Forms\Get $get) => $get('direction') === 'payable' ? 'اسم الدائن' : 'اسم المدين')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('المبلغ الإجمالي')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->prefix('ج.م')
                                    ->live(onBlur: true),

                                Forms\Components\DatePicker::make('start_date')
                                    ->label('تاريخ البدء')
                                    ->required()
                                    ->default(now())
                                    ->native(false),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('تاريخ الاستحقاق')
                                    ->native(false),

                                Forms\Components\FileUpload::make('attachment_path')
                                    ->label('مرفق (عقد/إيصال)')
                                    ->directory('debts')
                                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                                    ->maxSize(5120)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('notes')
                                    ->label('ملاحظات')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    Forms\Components\Wizard\Step::make('الأقساط')
                        ->icon('heroicon-o-calendar-days')
                        ->visible(fn(Forms\Get $get) => in_array($get('type'), ['loan', 'installment_debt']))
                        ->schema([
                            Forms\Components\TextInput::make('number_of_installments')
                                ->label('عدد الأقساط')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(120)
                                ->default(12)
                                ->helperText('سيتم توليد الأقساط تلقائياً بالتساوي بعد الحفظ')
                                ->live(),

                            Forms\Components\Placeholder::make('installments_preview')
                                ->label('معاينة الأقساط')
                                ->content(function (Forms\Get $get) {
                                    $total = floatval($get('total_amount') ?? 0);
                                    $count = intval($get('number_of_installments') ?? 0);
                                    if ($total <= 0 || $count <= 0) {
                                        return 'أدخل المبلغ الإجمالي وعدد الأقساط للمعاينة';
                                    }
                                    $perInstallment = round($total / $count, 2);
                                    return "سيتم تقسيم {$total} ج.م على {$count} قسط — كل قسط: {$perInstallment} ج.م (شهرياً)";
                                }),
                        ]),
                ])
                ->columnSpanFull()
                ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('direction')
                    ->label('الاتجاه')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'payable' ? 'علينا' : 'لنا')
                    ->color(fn(string $state) => $state === 'payable' ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'loan' => 'قرض',
                        'installment_debt' => 'أقساط',
                        'irregular_debt' => 'غير منتظم',
                        default => $state,
                    })
                    ->color('info'),

                Tables\Columns\TextColumn::make('creditor_or_debtor_name')
                    ->label('الدائن/المدين')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->money('EGP')
                    ->sortable()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'active' => 'نشطة',
                        'paid' => 'مسددة',
                        'overdue' => 'متأخرة',
                        'cancelled' => 'ملغاة',
                        default => $state,
                    })
                    ->color(fn(string $state) => match ($state) {
                        'active' => 'success',
                        'paid' => 'gray',
                        'overdue' => 'danger',
                        'cancelled' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('direction')
                    ->label('الاتجاه')
                    ->options([
                        'payable' => 'علينا',
                        'receivable' => 'لنا',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشطة',
                        'paid' => 'مسددة',
                        'overdue' => 'متأخرة',
                        'cancelled' => 'ملغاة',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'loan' => 'قرض',
                        'installment_debt' => 'أقساط',
                        'irregular_debt' => 'غير منتظم',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('بيانات المديونية')
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('العنوان'),

                        Infolists\Components\TextEntry::make('direction')
                            ->label('الاتجاه')
                            ->badge()
                            ->formatStateUsing(fn(string $state) => $state === 'payable' ? 'علينا' : 'لنا')
                            ->color(fn(string $state) => $state === 'payable' ? 'danger' : 'success'),

                        Infolists\Components\TextEntry::make('type')
                            ->label('النوع')
                            ->badge()
                            ->formatStateUsing(fn(string $state) => match ($state) {
                                'loan' => 'قرض',
                                'installment_debt' => 'أقساط',
                                'irregular_debt' => 'غير منتظم',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('creditor_or_debtor_name')
                            ->label('الدائن/المدين'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('المبلغ الإجمالي')
                            ->money('EGP'),

                        Infolists\Components\TextEntry::make('remaining_amount')
                            ->label('المتبقي')
                            ->money('EGP')
                            ->color(fn($state) => $state > 0 ? 'danger' : 'success'),

                        Infolists\Components\TextEntry::make('start_date')
                            ->label('تاريخ البدء')
                            ->date('Y-m-d'),

                        Infolists\Components\TextEntry::make('due_date')
                            ->label('تاريخ الاستحقاق')
                            ->date('Y-m-d'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn(string $state) => match ($state) {
                                'active' => 'نشطة',
                                'paid' => 'مسددة',
                                'overdue' => 'متأخرة',
                                'cancelled' => 'ملغاة',
                                default => $state,
                            })
                            ->color(fn(string $state) => match ($state) {
                                'active' => 'success',
                                'paid' => 'gray',
                                'overdue' => 'danger',
                                'cancelled' => 'warning',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InstallmentsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDebts::route('/'),
            'create' => Pages\CreateDebt::route('/create'),
            'view' => Pages\ViewDebt::route('/{record}'),
            'edit' => Pages\EditDebt::route('/{record}/edit'),
        ];
    }
}
