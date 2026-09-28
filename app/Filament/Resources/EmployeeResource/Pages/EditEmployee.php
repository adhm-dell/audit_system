<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('paySalary')
                ->label('صرف راتب الشهر الحالي')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->form(function () {
                    $netSalary = $this->record->getNetSalaryForMonth(now()->month, now()->year);
                    return [
                        \Filament\Forms\Components\TextInput::make('amount_display')
                            ->label('المبلغ المستحق الإجمالي')
                            ->disabled()
                            ->default($netSalary),
                            
                        \Filament\Forms\Components\Toggle::make('is_split')
                            ->label('تقسيم المبلغ (Split Payment)')
                            ->live(),
                            
                        \Filament\Forms\Components\Select::make('single_source')
                            ->label('المصدر')
                            ->options([
                                'cash' => 'نقدي',
                                'instapay' => 'إنستاباي',
                                'wallet' => 'محفظة',
                                'fawry' => 'فوري',
                            ])
                            ->default('cash')
                            ->visible(fn (\Filament\Forms\Get $get) => !$get('is_split'))
                            ->required(fn (\Filament\Forms\Get $get) => !$get('is_split')),
                            
                        \Filament\Forms\Components\TextInput::make('cash_amount')
                            ->label('المبلغ النقدي')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (\Filament\Forms\Get $get) => $get('is_split')),
                            
                        \Filament\Forms\Components\TextInput::make('instapay_amount')
                            ->label('مبلغ إنستاباي')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (\Filament\Forms\Get $get) => $get('is_split')),
                            
                        \Filament\Forms\Components\TextInput::make('wallet_amount')
                            ->label('مبلغ المحفظة الإلكترونية')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (\Filament\Forms\Get $get) => $get('is_split')),
                            
                        \Filament\Forms\Components\TextInput::make('fawry_amount')
                            ->label('مبلغ فوري')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (\Filament\Forms\Get $get) => $get('is_split')),
                    ];
                })
                ->action(function (array $data) {
                    $amount = $this->record->getNetSalaryForMonth(now()->month, now()->year);
                    if ($amount <= 0) {
                        \Filament\Notifications\Notification::make()
                            ->title('لا يوجد راتب مستحق')
                            ->danger()
                            ->send();
                        return;
                    }

                    $cash = 0;
                    $instapay = 0;
                    $wallet = 0;
                    $fawry = 0;

                    if (empty($data['is_split'])) {
                        $source = $data['single_source'];
                        if ($source === 'cash') $cash = $amount;
                        elseif ($source === 'instapay') $instapay = $amount;
                        elseif ($source === 'wallet') $wallet = $amount;
                        elseif ($source === 'fawry') $fawry = $amount;
                    } else {
                        $cash = floatval($data['cash_amount'] ?? 0);
                        $instapay = floatval($data['instapay_amount'] ?? 0);
                        $wallet = floatval($data['wallet_amount'] ?? 0);
                        $fawry = floatval($data['fawry_amount'] ?? 0);
                        
                        $totalInput = $cash + $instapay + $wallet + $fawry;
                        if (abs($totalInput - $amount) > 0.01) {
                            \Filament\Notifications\Notification::make()
                                ->title('مجموع المبالغ المقسمة يجب أن يساوي الراتب المستحق (' . $amount . ')')
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    // Check balance
                    $service = app(\App\Services\SafeBalanceService::class);
                    if ($cash > 0 && $cash > $service->getBalance('cash')) {
                        \Filament\Notifications\Notification::make()->title('الرصيد النقدي غير كافٍ')->danger()->send();
                        return;
                    }
                    if ($instapay > 0 && $instapay > $service->getBalance('digital', 'instapay')) {
                        \Filament\Notifications\Notification::make()->title('رصيد إنستاباي غير كافٍ')->danger()->send();
                        return;
                    }
                    if ($wallet > 0 && $wallet > $service->getBalance('digital', 'wallet')) {
                        \Filament\Notifications\Notification::make()->title('رصيد المحفظة غير كافٍ')->danger()->send();
                        return;
                    }
                    if ($fawry > 0 && $fawry > $service->getBalance('digital', 'fawry')) {
                        \Filament\Notifications\Notification::make()->title('رصيد فوري غير كافٍ')->danger()->send();
                        return;
                    }

                    \App\Models\Expense::create([
                        'employee_id' => $this->record->id,
                        'expense_date' => now(),
                        'cash_amount' => $cash,
                        'instapay_amount' => $instapay,
                        'wallet_amount' => $wallet,
                        'fawry_amount' => $fawry,
                        'category' => 'salary',
                        'title' => 'راتب شهر ' . now()->format('m-Y') . ' - ' . $this->record->name,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('تم صرف الراتب بنجاح')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->getNetSalaryForMonth(now()->month, now()->year) > 0),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
