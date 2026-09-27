<?php

namespace App\Filament\Pages;

use App\Models\NotificationSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'الإدارة';
    protected static ?string $navigationLabel = 'إعدادات التنبيهات';
    protected static ?string $title = 'إعدادات التنبيهات';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.notification-settings';

    public array $settings = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isOwnerAdmin() ?? false;
    }

    public function mount(): void
    {
        $allSettings = NotificationSetting::all();

        foreach ($allSettings as $setting) {
            $this->settings[$setting->type] = [
                'id' => $setting->id,
                'is_enabled' => $setting->is_enabled,
                'threshold_amount' => $setting->threshold_amount,
                'days_before_due' => $setting->days_before_due,
            ];
        }
    }

    public function save(): void
    {
        foreach ($this->settings as $type => $data) {
            NotificationSetting::where('type', $type)->update([
                'is_enabled' => $data['is_enabled'] ?? false,
                'threshold_amount' => $data['threshold_amount'] ?? null,
                'days_before_due' => $data['days_before_due'] ?? null,
            ]);
        }

        Notification::make()
            ->title('تم حفظ الإعدادات بنجاح')
            ->success()
            ->send();
    }

    public function getSettingLabels(): array
    {
        return [
            'low_cash_balance' => [
                'title' => 'تنبيه انخفاض رصيد الكاش',
                'description' => 'تنبيه عند انخفاض رصيد الكاش في الخزنة عن حد معين',
                'icon' => 'heroicon-o-exclamation-triangle',
                'has_threshold' => true,
                'has_days' => false,
            ],
            'low_digital_balance' => [
                'title' => 'تنبيه انخفاض رصيد الشبكة',
                'description' => 'تنبيه عند انخفاض رصيد الشبكة/الرقمي عن حد معين',
                'icon' => 'heroicon-o-exclamation-triangle',
                'has_threshold' => true,
                'has_days' => false,
            ],
            'installment_due_soon' => [
                'title' => 'تنبيه قسط قادم',
                'description' => 'تنبيه قبل استحقاق الأقساط بعدد أيام محدد',
                'icon' => 'heroicon-o-calendar-days',
                'has_threshold' => false,
                'has_days' => true,
            ],
            'installment_overdue' => [
                'title' => 'تنبيه قسط متأخر',
                'description' => 'تنبيه عند تأخر سداد الأقساط',
                'icon' => 'heroicon-o-clock',
                'has_threshold' => false,
                'has_days' => false,
            ],
        ];
    }
}
