<?php

namespace App\Filament\Resources\DebtResource\RelationManagers;

use App\Models\Debt;
use App\Models\DebtInstallment;
use App\Models\DebtPayment;
use App\Services\SafeBalanceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'المدفوعات';
    protected static ?string $modelLabel = 'دفعة';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('installment_id')
                    ->label('القسط (اختياري)')
                    ->options(function () {
                        $debt = $this->getOwnerRecord();
                        return $debt->installments()
                            ->whereIn('status', ['pending', 'partial', 'overdue'])
                            ->get()
                            ->mapWithKeys(fn($i) => [
                                $i->id => "قسط #{$i->installment_number} — " . number_format($i->amount - $i->paid_amount, 2) . " ج.م متبقي",
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
                    ->default('cash'),

                Forms\Components\DatePicker::make('payment_date')
                    ->label('تاريخ الدفع')
                    ->required()
                    ->default(now())
                    ->native(false),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('payment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('installment.installment_number')
                    ->label('رقم القسط')
                    ->default('—')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('EGP')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('EGP')->label('الإجمالي')),

                Tables\Columns\TextColumn::make('source')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'cash' ? 'نقدي' : 'شبكة')
                    ->color(fn(string $state) => $state === 'cash' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('تسجيل دفعة'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
