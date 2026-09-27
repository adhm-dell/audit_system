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

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'cash' ? 'نقدي' : 'شبكة')
                    ->color(fn(string $state) => $state === 'cash' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('installment.installment_number')
                    ->label('رقم القسط')
                    ->default('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('debt_id')
                    ->label('المديونية')
                    ->options(Debt::pluck('title', 'id'))
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
