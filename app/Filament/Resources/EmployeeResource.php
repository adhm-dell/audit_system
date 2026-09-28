<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers\ExpensesRelationManager;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'الإدارة';
    protected static ?string $navigationLabel = 'الموظفين والعمال';
    protected static ?string $modelLabel = 'موظف';
    protected static ?string $pluralModelLabel = 'الموظفين';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ملخص الراتب')
                    ->schema([
                        Forms\Components\Placeholder::make('net_salary')
                            ->label('صافي الراتب المستحق (للشهر الحالي)')
                            ->content(function (?Employee $record) {
                                if (! $record) {
                                    return '-';
                                }
                                $net = $record->getNetSalaryForMonth(now()->month, now()->year);
                                return number_format($net, 2) . ' ج.م';
                            }),
                    ])
                    ->visible(fn (?Employee $record) => $record !== null),

                Forms\Components\Section::make('بيانات الموظف')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->label('الوظيفة')
                            ->options(Employee::roleLabels())
                            ->required(),

                        Forms\Components\TextInput::make('monthly_salary')
                            ->label('الراتب الشهري')
                            ->numeric()
                            ->prefix('ج.م')
                            ->helperText('يُترك فارغاً لعمال اليومية'),

                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\DatePicker::make('hire_date')
                            ->label('تاريخ التعيين')
                            ->required()
                            ->default(now()),

                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),

                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('الوظيفة')
                    ->formatStateUsing(fn (string $state) => Employee::roleLabels()[$state] ?? $state),

                Tables\Columns\TextColumn::make('monthly_salary')
                    ->label('الراتب الشهري')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('advances_this_month')
                    ->label('سلف هذا الشهر')
                    ->money('EGP')
                    ->getStateUsing(function (Employee $record) {
                        return $record->expenses()
                            ->where('category', 'employee_advance')
                            ->whereMonth('expense_date', now()->month)
                            ->whereYear('expense_date', now()->year)
                            ->sum('amount');
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('الوظيفة')
                    ->options(Employee::roleLabels()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ExpensesRelationManager::class,
            \App\Filament\Resources\EmployeeResource\RelationManagers\DailyEnvelopeItemsRelationManager::class,
            \App\Filament\Resources\EmployeeResource\RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
