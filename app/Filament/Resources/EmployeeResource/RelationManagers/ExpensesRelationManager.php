<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';
    protected static ?string $title = 'سجل السلف والرواتب';

    public function form(Form $form): Form
    {
        return \App\Filament\Resources\ExpenseResource::form($form);
    }

    public function table(Table $table): Table
    {
        return \App\Filament\Resources\ExpenseResource::table($table);
    }
}
