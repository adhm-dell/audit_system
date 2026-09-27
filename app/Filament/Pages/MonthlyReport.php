<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Models\DailyEnvelope;
use App\Models\DebtPayment;
use App\Models\BarEnvelope;
use App\Models\Debt;
use App\Models\OwnerWithdrawal;
use App\Services\SafeBalanceService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class MonthlyReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'الخزنة والتقارير';
    protected static ?string $navigationLabel = 'التقرير الشهري';
    protected static ?string $title = 'التقرير الشهري';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.monthly-report';

    public ?int $selectedMonth = null;
    public ?int $selectedYear = null;

    public function mount(): void
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('selectedMonth')
                        ->label('الشهر')
                        ->options([
                            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
                        ])
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('selectedYear')
                        ->label('السنة')
                        ->options(function () {
                            $years = [];
                            for ($y = 2024; $y <= now()->year + 1; $y++) {
                                $years[$y] = $y;
                            }
                            return $years;
                        })
                        ->required()
                        ->live(),
                ]),
            ]);
    }

    public function getReportData(): array
    {
        $start = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $service = app(SafeBalanceService::class);

        // Deposits
        $totalCashDeposits = DailyEnvelope::whereBetween('envelope_date', [$start, $end])->sum('net_cash_to_safe');
        $totalDigitalDeposits = DailyEnvelope::whereBetween('envelope_date', [$start, $end])->sum('net_digital');

        $barTotalCash = BarEnvelope::whereBetween('bar_date', [$start, $end])->sum('cash_total');
        $barTotalDigital = BarEnvelope::whereBetween('bar_date', [$start, $end])->sum('network_total');

        $barDebtsCreated = BarEnvelope::whereBetween('bar_date', [$start, $end])->sum('debts_total');
        $barDebtsCollected = DebtPayment::whereBetween('payment_date', [$start, $end])
            ->whereHas('debt', fn($q) => $q->where('direction', 'receivable')->where('creditor_or_debtor_name', 'عملاء البار'))
            ->sum('amount');

        // Network totals by channel
        // Daily Envelopes
        $gymInstapay = DailyEnvelope::whereBetween('envelope_date', [$start, $end])->sum('network_instapay_total');
        $gymWallet = DailyEnvelope::whereBetween('envelope_date', [$start, $end])->sum('network_wallet_total');
        $gymFawry = DailyEnvelope::whereBetween('envelope_date', [$start, $end])->sum('network_fawry_total');

        // Bar Envelopes
        $barInstapay = BarEnvelope::whereBetween('bar_date', [$start, $end])->sum('network_instapay_total');
        $barWallet = BarEnvelope::whereBetween('bar_date', [$start, $end])->sum('network_wallet_total');
        $barFawry = BarEnvelope::whereBetween('bar_date', [$start, $end])->sum('network_fawry_total');
        
        // Expenses, Withdrawals, DebtPayments (digital)
        $digitalExpenses = Expense::whereBetween('expense_date', [$start, $end])->where('source', 'digital')->get();
        $digitalWithdrawals = OwnerWithdrawal::whereBetween('withdrawal_date', [$start, $end])->where('source', 'digital')->get();
        $digitalDebtPayments = DebtPayment::whereBetween('payment_date', [$start, $end])->where('source', 'digital')->get();

        $totalInstapay = $gymInstapay + $barInstapay
            + $digitalExpenses->where('digital_channel', 'instapay')->sum('amount')
            + $digitalWithdrawals->where('digital_channel', 'instapay')->sum('amount')
            + $digitalDebtPayments->where('digital_channel', 'instapay')->sum('amount');
            
        $totalWallet = $gymWallet + $barWallet
            + $digitalExpenses->where('digital_channel', 'wallet')->sum('amount')
            + $digitalWithdrawals->where('digital_channel', 'wallet')->sum('amount')
            + $digitalDebtPayments->where('digital_channel', 'wallet')->sum('amount');

        $totalFawry = $gymFawry + $barFawry
            + $digitalExpenses->where('digital_channel', 'fawry')->sum('amount')
            + $digitalWithdrawals->where('digital_channel', 'fawry')->sum('amount')
            + $digitalDebtPayments->where('digital_channel', 'fawry')->sum('amount');

        // Withdrawals per owner
        $withdrawalsByOwner = OwnerWithdrawal::with('owner')
            ->whereBetween('withdrawal_date', [$start, $end])
            ->get()
            ->groupBy('owner_id')
            ->map(fn($items) => [
                'name' => $items->first()->owner->name ?? 'غير محدد',
                'total' => $items->sum('amount'),
            ])
            ->values()
            ->toArray();

        $totalWithdrawals = array_sum(array_column($withdrawalsByOwner, 'total'));

        // Expenses by category
        $expensesByCategory = Expense::whereBetween('expense_date', [$start, $end])
            ->get()
            ->groupBy('category')
            ->map(fn($items, $cat) => [
                'label' => Expense::categoryLabels()[$cat] ?? $cat,
                'total' => $items->sum('amount'),
            ])
            ->values()
            ->toArray();

        $totalExpenses = array_sum(array_column($expensesByCategory, 'total'));

        // Debt payments
        $totalDebtPayments = DebtPayment::whereBetween('payment_date', [$start, $end])->sum('amount');

        // Opening & closing balance
        $openingCash = $service->getBalanceAsOf($start->copy()->subDay(), 'cash');
        $openingDigital = $service->getBalanceAsOf($start->copy()->subDay(), 'digital');
        $closingCash = $service->getBalanceAsOf($end, 'cash');
        $closingDigital = $service->getBalanceAsOf($end, 'digital');

        return [
            'totalCashDeposits' => $totalCashDeposits,
            'totalDigitalDeposits' => $totalDigitalDeposits,
            'barTotalCash' => $barTotalCash,
            'barTotalDigital' => $barTotalDigital,
            'barDebtsCreated' => $barDebtsCreated,
            'barDebtsCollected' => $barDebtsCollected,
            'totalInstapay' => $totalInstapay,
            'totalWallet' => $totalWallet,
            'totalFawry' => $totalFawry,
            'withdrawalsByOwner' => $withdrawalsByOwner,
            'totalWithdrawals' => $totalWithdrawals,
            'expensesByCategory' => $expensesByCategory,
            'totalExpenses' => $totalExpenses,
            'totalDebtPayments' => $totalDebtPayments,
            'openingCash' => $openingCash,
            'openingDigital' => $openingDigital,
            'closingCash' => $closingCash,
            'closingDigital' => $closingDigital,
        ];
    }
}
