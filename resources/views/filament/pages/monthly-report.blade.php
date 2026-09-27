<x-filament-panels::page>
    <form wire:submit.prevent="">
        {{ $this->form }}
    </form>

    @php
        $data = $this->getReportData();
    @endphp

    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2">
        {{-- Opening Balance --}}
        <x-filament::section icon="heroicon-o-arrow-right-start-on-rectangle" heading="رصيد أول المدة">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">كاش</span>
                    <span class="font-bold {{ $data['openingCash'] >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                        {{ number_format($data['openingCash'], 2) }} ج.م
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">شبكة</span>
                    <span class="font-bold {{ $data['openingDigital'] >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                        {{ number_format($data['openingDigital'], 2) }} ج.م
                    </span>
                </div>
            </div>
        </x-filament::section>

        {{-- Closing Balance --}}
        <x-filament::section icon="heroicon-o-arrow-right-end-on-rectangle" heading="رصيد آخر المدة">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">كاش</span>
                    <span class="font-bold {{ $data['closingCash'] >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                        {{ number_format($data['closingCash'], 2) }} ج.م
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">شبكة</span>
                    <span class="font-bold {{ $data['closingDigital'] >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                        {{ number_format($data['closingDigital'], 2) }} ج.م
                    </span>
                </div>
            </div>
        </x-filament::section>
    </div>

    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-3">
        {{-- Total Deposits --}}
        <x-filament::section icon="heroicon-o-arrow-down-tray" heading="إجمالي إيداعات الجيم">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">كاش</span>
                    <span class="font-bold text-success-500">{{ number_format($data['totalCashDeposits'], 2) }} ج.م</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">شبكة</span>
                    <span class="font-bold text-success-500">{{ number_format($data['totalDigitalDeposits'], 2) }} ج.م</span>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-building-storefront" heading="إجمالي إيداعات البار">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">كاش</span>
                    <span class="font-bold text-success-500">{{ number_format($data['barTotalCash'], 2) }} ج.م</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">شبكة</span>
                    <span class="font-bold text-success-500">{{ number_format($data['barTotalDigital'], 2) }} ج.م</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Total Expenses --}}
        <x-filament::section icon="heroicon-o-receipt-percent" heading="إجمالي المصاريف">
            <p class="text-2xl font-bold text-danger-500">{{ number_format($data['totalExpenses'], 2) }} ج.م</p>
        </x-filament::section>
    </div>

    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-3">
        <x-filament::section icon="heroicon-o-signal" heading="تفاصيل قنوات الدفع الرقمي">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">إنستاباي</span>
                    <span class="font-bold">{{ number_format($data['totalInstapay'], 2) }} ج.م</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">محافظ إلكترونية</span>
                    <span class="font-bold">{{ number_format($data['totalWallet'], 2) }} ج.م</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">فوري</span>
                    <span class="font-bold">{{ number_format($data['totalFawry'], 2) }} ج.م</span>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-clipboard-document-list" heading="حركة مديونيات البار">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">إجمالي المديونيات المضافة</span>
                    <span class="font-bold text-danger-500">{{ number_format($data['barDebtsCreated'], 2) }} ج.م</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">المديونيات المحصلة</span>
                    <span class="font-bold text-success-500">{{ number_format($data['barDebtsCollected'], 2) }} ج.م</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Total Withdrawals --}}
        <x-filament::section icon="heroicon-o-arrow-up-tray" heading="إجمالي المسحوبات (شريك)">
            <p class="text-2xl font-bold text-warning-500">{{ number_format($data['totalWithdrawals'], 2) }} ج.م</p>
        </x-filament::section>
    </div>

    {{-- Withdrawals by Owner --}}
    @if (count($data['withdrawalsByOwner']) > 0)
        <x-filament::section icon="heroicon-o-user-group" heading="المسحوبات حسب الشريك" class="mt-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 text-right font-medium text-gray-500 dark:text-gray-400">الشريك</th>
                            <th class="py-2 text-left font-medium text-gray-500 dark:text-gray-400">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['withdrawalsByOwner'] as $w)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">{{ $w['name'] }}</td>
                                <td class="py-2 text-left font-semibold">{{ number_format($w['total'], 2) }} ج.م</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Expenses by Category --}}
    @if (count($data['expensesByCategory']) > 0)
        <x-filament::section icon="heroicon-o-tag" heading="المصاريف حسب التصنيف" class="mt-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 text-right font-medium text-gray-500 dark:text-gray-400">التصنيف</th>
                            <th class="py-2 text-left font-medium text-gray-500 dark:text-gray-400">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['expensesByCategory'] as $e)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">{{ $e['label'] }}</td>
                                <td class="py-2 text-left font-semibold">{{ number_format($e['total'], 2) }} ج.م</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- Debt Payments --}}
    <x-filament::section icon="heroicon-o-credit-card" heading="إجمالي مدفوعات المديونيات" class="mt-6">
        <p class="text-2xl font-bold text-info-500">{{ number_format($data['totalDebtPayments'], 2) }} ج.م</p>
    </x-filament::section>
</x-filament-panels::page>
