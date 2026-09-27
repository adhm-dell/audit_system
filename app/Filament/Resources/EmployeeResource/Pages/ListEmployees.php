<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Expense;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close_salaries')
                ->label('قفل مرتبات الشهر')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->form([
                    Forms\Components\DatePicker::make('month')
                        ->label('الشهر')
                        ->default(now()->startOfMonth())
                        ->required()
                        ->native(false)
                        ->displayFormat('Y-m'),
                    
                    Forms\Components\Select::make('source')
                        ->label('المصدر')
                        ->options([
                            'cash' => 'نقدي',
                            'digital' => 'شبكة/رقمي'
                        ])
                        ->default('cash')
                        ->required()
                        ->live(),
                        
                    Forms\Components\Select::make('digital_channel')
                        ->label('قناة الدفع الرقمي')
                        ->options([
                            'instapay' => 'إنستاباي',
                            'wallet' => 'محفظة إلكترونية',
                            'fawry' => 'فوري',
                        ])
                        ->visible(fn(Forms\Get $get) => $get('source') === 'digital'),
                ])
                ->action(function (array $data) {
                    $month = \Carbon\Carbon::parse($data['month']);
                    $employees = Employee::where('is_active', true)
                        ->whereNotNull('monthly_salary')
                        ->get();
                        
                    $count = 0;
                    foreach ($employees as $employee) {
                        $advances = $employee->expenses()
                            ->where('category', 'employee_advance')
                            ->whereMonth('expense_date', $month->month)
                            ->whereYear('expense_date', $month->year)
                            ->sum('amount');
                            
                        $netDue = $employee->monthly_salary - $advances;
                        
                        if ($netDue > 0) {
                            Expense::create([
                                'expense_date' => now(),
                                'title' => 'راتب شهر ' . $month->format('Y-m') . ' - ' . $employee->name,
                                'category' => 'salary',
                                'amount' => $netDue,
                                'source' => $data['source'],
                                'digital_channel' => $data['digital_channel'] ?? null,
                                'employee_id' => $employee->id,
                                'notes' => "الراتب: {$employee->monthly_salary}, السلف: {$advances}",
                            ]);
                            $count++;
                        }
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('تم قفل المرتبات بنجاح')
                        ->body("تم إنشاء رواتب لـ {$count} موظف.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('قفل مرتبات الشهر')
                ->modalDescription('سيتم حساب المرتبات مطروحاً منها سلف الشهر المحدد، وإنشاء سجل "راتب" لكل موظف نشط له راتب ثابت.'),
            
            Actions\CreateAction::make(),
        ];
    }
}
